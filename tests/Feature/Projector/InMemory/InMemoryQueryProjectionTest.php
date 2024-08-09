<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector;

use Storm\Clock\Clock;
use Storm\Projector\Scope\QueryProjectorScope;
use Storm\Tests\Domain\Balance\BalanceAdded;
use Storm\Tests\Domain\Balance\BalanceCreated;
use Storm\Tests\Domain\Balance\BalanceId;
use Storm\Tests\Domain\Balance\BalanceSubtracted;
use Storm\Tests\Feature\Projector\InMemory\Concern\InMemoryProjectionExpectationTrait;
use Storm\Tests\Feature\Projector\InMemory\Concern\InMemoryQueryProjectionTestBaseTrait;
use Storm\Tests\Feature\Projector\InMemory\Factory\InMemoryTestingFactory;

use function array_merge;
use function count;

uses(
    InMemoryQueryProjectionTestBaseTrait::class,
    InMemoryProjectionExpectationTrait::class
);

beforeEach(function () {
    $this->factory = new InMemoryTestingFactory();
    $this->expectedStateEvents = [
        BalanceCreated::class,
        BalanceAdded::class,
        BalanceSubtracted::class,
        BalanceSubtracted::class,
    ];
});

test('query projection', function (?string $descriptionId) {
    $this->setupProjection(descriptionId: $descriptionId);

    $this->makeEventStore($streamName = 'balance', $balanceId = BalanceId::create())
        ->withBalanceCreated(1, 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $this->projector
        ->initialize(fn (): array => [])
        ->subscribeToStream($streamName)
        ->when($this->getQueryReactor(), $this->getThenReactor())
        ->filter($this->factory->getQueryFilter())
        ->run(false);

    $this->assertProjectionState(
        [
            'balances' => [$balanceId->toString() => 100],
            'events' => $this->expectedStateEvents,
        ]
    );

    $this->assertProjectionReport(cycle: 1, ackedEvent: 4, totalEvent: 4, descriptionId: $descriptionId);
})->with('projection optional description id');

test('query event scope with one stream event', function () {
    $this->setupProjection();

    $this->makeEventStore($streamName = 'account', BalanceId::create())
        ->withBalanceCreated(1, 100);

    $reactors = [function (BalanceCreated $event) {}];
    $thenReactor = function (QueryProjectorScope $scope): void {
        expect($scope)->toBeInstanceOf(QueryProjectorScope::class)
            ->and($scope->streamName())->toBe('account')
            ->and($scope->clock())->toBeInstanceOf(Clock::class);
    };

    $this->projector
        ->subscribeToStream($streamName)
        ->when($reactors, $thenReactor)
        ->filter($this->factory->getQueryFilter())
        ->run(false);
});

test('query projection with many streams', function () {
    $this->setupProjection();

    $this->makeEventStore($streamOne = 'balance_one', $balanceIdOne = BalanceId::create())
        ->withBalanceCreated(1, 600)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $this->makeEventStore($streamTwo = 'balance_two', $balanceIdTwo = BalanceId::create())
        ->withBalanceCreated(1, 500)
        ->withVersioningAmount([[2, 100], [3, -50], [4, -25]]);

    $this->projector
        ->initialize(fn (): array => [])
        ->subscribeToStream($streamOne, $streamTwo)
        ->when($this->getQueryReactor(), $this->getThenReactor())
        ->filter($this->factory->getQueryFilter())
        ->run(false);

    $this->assertProjectionState(
        [
            'balances' => [
                $balanceIdOne->toString() => 600,
                $balanceIdTwo->toString() => 525,
            ],
            'events' => array_merge($this->expectedStateEvents, $this->expectedStateEvents),
        ]
    );

    $this->assertProjectionReport(cycle: 1, ackedEvent: 8, totalEvent: 8);
});

test('query projection from stream partition', function () {
    $this->setupProjection();

    $this->makeEventStore($streamOne = 'balance-one', $balanceIdOne = BalanceId::create())
        ->withBalanceCreated(1, 1000)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -120]]);

    $this->makeEventStore($streamTwo = 'balance-two', $balanceIdTwo = BalanceId::create())
        ->withBalanceCreated(1, 500)
        ->withVersioningAmount([[2, 100], [3, -50], [4, -25]]);

    $this->assertStreamExists($streamOne, true);
    $this->assertStreamExists($streamTwo, true);

    $this->projector
        ->initialize(fn (): array => [])
        ->subscribeToPartition('balance')
        ->when($this->getQueryReactor(), $this->getThenReactor())
        ->filter($this->factory->getQueryFilter())
        ->run(false);

    $this->assertProjectionState(
        [
            'balances' => [
                $balanceIdOne->toString() => 930,
                $balanceIdTwo->toString() => 525,
            ],
            'events' => array_merge($this->expectedStateEvents, $this->expectedStateEvents),
        ]
    );

    $this->assertProjectionReport(cycle: 1, ackedEvent: 8, totalEvent: 8);
});

test('query projection from stream partition which does not exist', function () {
    $this->setupProjection();

    $this->assertStreamExists('balance', false);

    $this->projector
        ->initialize(fn (): array => [])
        ->subscribeToPartition('balance')
        ->when($this->getQueryReactor(), $this->getThenReactor())
        ->filter($this->factory->getQueryFilter())
        ->run(false);

    $this->assertProjectionState([]);
});
test('stop query projection from query scope', function () {
    $this->setupProjection();

    $this->makeEventStore($streamName = 'account', $balanceId = BalanceId::create())
        ->withBalanceCreated(1, 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $thenReactor = $this->getThenReactor(keepRunning: true, stopAt: ['events', 2]);

    $this->projector
        ->initialize(fn (): array => [])
        ->subscribeToStream($streamName)
        ->when($this->getQueryReactor(), $thenReactor)
        ->filter($this->factory->getQueryFilter())
        ->run(true);

    $this->assertProjectionState(
        [
            'balances' => [$balanceId->toString() => 300],
            'events' => [BalanceCreated::class, BalanceAdded::class],
        ]
    );

    $this->assertProjectionReport(cycle: 1, ackedEvent: 2, totalEvent: 2);
});

test('query projection with a custom query filter', function () {
    $this->setupProjection();

    $this->makeEventStore($streamName = 'account', $balanceId = BalanceId::create())
        ->withBalanceCreated(1, 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $customQueryFilter = $this->customFilterQueryEvents(BalanceSubtracted::class);

    $this->projector
        ->initialize(fn (): array => [])
        ->subscribeToStream($streamName)
        ->when($this->getQueryReactor(), $this->getThenReactor())
        ->filter($customQueryFilter)
        ->run(false);

    $this->assertProjectionState(
        [
            'balances' => [$balanceId->toString() => -200],
            'events' => [BalanceSubtracted::class, BalanceSubtracted::class],
        ]
    );

    $this->assertProjectionReport(cycle: 1, ackedEvent: 2, totalEvent: 2);
});

test('query projection running in background and stop projection from event scope', function () {
    $this->setupProjection();

    $this->makeEventStore($streamName = 'account', BalanceId::create())
        ->withBalanceCreated(1, 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $reactors = [
        function (BalanceCreated $event) {},
        function (BalanceAdded $event) {},
        function (BalanceSubtracted $event) {},
    ];

    $thenReactor = function (QueryProjectorScope $scope): void {
        $scope->userState()->push('events', $scope->event()::class);

        if (count($scope->userState()['events']) === 2) {
            $scope->stop();
        }
    };

    $this->projector
        ->initialize(fn (): array => ['events' => []])
        ->subscribeToStream($streamName)
        ->when($reactors, $thenReactor)
        ->filter($this->factory->getQueryFilter())
        ->run(true);

    $this->assertPartialProjectionState('events', [BalanceCreated::class, BalanceAdded::class]);

    $this->assertProjectionReport(cycle: 1, ackedEvent: 2, totalEvent: 2);
});

test('query projection running in background and stop projection from event scope 2', function () {
    $this->setupProjection();

    $this->makeEventStore($streamName = 'account', BalanceId::create())
        ->withBalanceCreated(1, 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $reactors = [
        function (BalanceCreated $event) {},
        function (BalanceAdded $event) {},
        function (BalanceSubtracted $event) {
            $this->stop();
        },
    ];

    $thenReactor = function (QueryProjectorScope $scope): void {
        $scope->userState()->push('events', $scope->event()::class);
    };

    $this->projector
        ->initialize(fn (): array => ['events' => []])
        ->subscribeToStream($streamName)
        ->when($reactors, $thenReactor)
        ->filter($this->factory->getQueryFilter())
        ->run(true);

    $this->assertPartialProjectionState('events', [
        BalanceCreated::class, BalanceAdded::class, BalanceSubtracted::class,
    ]);

    $this->assertProjectionReport(cycle: 1, ackedEvent: 3, totalEvent: 3);
});

test('query projection running once and does not stop on gap', function () {
    $this->setupProjection();

    $this->makeEventStore($streamName = 'balance', $balanceId = BalanceId::create())
        ->withBalanceCreated(1, 100)
        ->withVersioningAmount([[5, 200], [20, -150], [50, -50]]);

    $this->projector
        ->initialize(fn (): array => [])
        ->subscribeToStream($streamName)
        ->when($this->getQueryReactor(), $this->getThenReactor())
        ->filter($this->factory->getQueryFilter())
        ->run(false);

    $this->assertProjectionState(
        [
            'balances' => [$balanceId->toString() => 100],
            'events' => $this->expectedStateEvents,
        ]
    );

    $this->assertProjectionReport(cycle: 1, ackedEvent: 4, totalEvent: 4);
});

test('query projection running once and stop on gap when retries are configured', function () {
    $this->setupProjection(options: ['retries' => [1, 2, 3]]);

    $streamName = 'balance';
    $this->makeEventStore($streamName, $balanceId = BalanceId::create())
        ->withBalanceCreated(1, 600)
        ->withBalanceAdded(3, 200);

    $this->projector
        ->initialize(fn (): array => [])
        ->subscribeToStream($streamName)
        ->when($this->getQueryReactor(), $this->getThenReactor())
        ->filter($this->factory->getQueryFilter())
        ->run(false);

    $this->assertProjectionState(
        [
            'balances' => [$balanceId->toString() => 600],
            'events' => [BalanceCreated::class],
        ]
    );

    $this->assertProjectionReport(cycle: 1, ackedEvent: 1, totalEvent: 1);
});

test('query projection running in background and resolve gap when retries are configured', function (array $retries) {
    $this->setupProjection(options: ['retries' => $retries]);

    $this->makeEventStore($streamName = 'balance', $balanceId = BalanceId::create())
        ->withBalanceCreated(1, 600)
        ->withBalanceAdded(3, 200)
        ->withBalanceSubtracted(10, 50);

    $thenReactor = $this->getThenReactor(keepRunning: true, stopAt: ['events', 3]);

    $this->projector
        ->initialize(fn (): array => [])
        ->subscribeToStream($streamName)
        ->when($this->getQueryReactor(), $thenReactor)
        ->filter($this->factory->getQueryFilter())
        ->run(true);

    $this->assertProjectionState(
        [
            'balances' => [$balanceId->toString() => 750],
            'events' => [BalanceCreated::class, BalanceAdded::class, BalanceSubtracted::class],
        ]
    );

    $expectedCycles = $this->calculateExpectedCycles(
        numberOfEventWithNoGap: 1,
        numberOfRetry: count($retries),
        numberOfEventWithGap: 2
    );
    $this->assertProjectionReport(cycle: $expectedCycles, ackedEvent: 3, totalEvent: 3);
})->with('projection options with non empty retries');

test('should perform when max batch is reached with reset and sleep', function () {
    $this->setupProjection(
        options: ['blockSize' => 2, 'sleep' => ['base' => 100, 'factor' => 1, 'max' => 1000]]
    );

    $this->makeEventStore($streamName = 'balance', $balanceId = BalanceId::create())
        ->withBalanceCreated(1, 600)
        ->withBalanceAdded(2, 200)
        ->withBalanceAdded(3, 200)
        ->withBalanceAdded(4, 200)
        ->withBalanceAdded(5, 200);

    $this->projector
        ->initialize(fn (): array => [])
        ->subscribeToStream($streamName)
        ->when($this->getQueryReactor(), $this->getThenReactor())
        ->filter($this->factory->getQueryFilter())
        ->run(false);

    $this->assertPartialProjectionState('balances', [$balanceId->toString() => 1400]);

    $this->assertProjectionReport(cycle: 1, ackedEvent: 5, totalEvent: 5);
});

test('stop projection from query projector', function () {
    $this->setupProjection();

    $this->makeEventStore($streamName = 'balance')
        ->withBalanceCreated(1, 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $reactors = [
        function (BalanceCreated $event) {
            $this->userState()->increment('events');
        },
        function (BalanceAdded $event) {
            $this->userState()->increment('events');
        },
        function (BalanceSubtracted $event) {
            $this->userState()->increment('events');
        },
    ];

    $projector = $this->projector;
    $thenReactor = function (QueryProjectorScope $scope) use ($projector): void {
        if ($scope->userState()->integer('events') === 4) {
            $projector->stop();
        }
    };

    $this->projector
        ->initialize(fn (): array => ['events' => 0])
        ->subscribeToStream($streamName)
        ->when($reactors, $thenReactor)
        ->filter($this->factory->getQueryFilter())
        ->run(true);

    $this->assertProjectionReport(cycle: 1, ackedEvent: 4, totalEvent: 4);
});
