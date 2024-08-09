<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector;

use Exception;
use Storm\Clock\Clock;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Scope\ReadModelScope;
use Storm\Projector\Support\ReadModel\InMemoryReadModel;
use Storm\Tests\Domain\Balance\BalanceAdded;
use Storm\Tests\Domain\Balance\BalanceCreated;
use Storm\Tests\Domain\Balance\BalanceSubtracted;
use Storm\Tests\Feature\Projector\InMemory\Concern\InMemoryProjectionExpectationTrait;
use Storm\Tests\Feature\Projector\InMemory\Concern\InMemoryReadModelProjectionTestBaseTrait;
use Storm\Tests\Feature\Projector\InMemory\Factory\InMemoryTestingFactory;
use Storm\Tests\Stubs\Double\Message\SomeEvent;

use function array_merge;
use function count;

uses(
    InMemoryReadModelProjectionTestBaseTrait::class,
    InMemoryProjectionExpectationTrait::class
);

beforeEach(function () {
    $this->factory = new InMemoryTestingFactory();
    $this->readModel = new InMemoryReadModel();
    $this->expectedStateEvents = [
        BalanceCreated::class,
        BalanceAdded::class,
        BalanceSubtracted::class,
        BalanceSubtracted::class,
    ];
});

test('reads events from the beginning of the stream', function (?string $descriptionId) {
    $this->setupProjection(
        [[$streamName = 'account', null]],
        projectionName: $projectionName = 'balance',
        descriptionId: $descriptionId,
    );

    $this->balanceEventStore($streamName)
        ->withBalanceCreated(version: 1, amount: 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($streamName)
        ->when($this->getReadModelReactor(), $this->getThenReactor())
        ->filter($this->factory->getQueryFilter())
        ->run(inBackground: false);

    $this->assertProjectionState(['total' => 100, 'events' => $this->expectedStateEvents]);
    $this->assertReadModelBalance($streamName, 100);
    $this->assertProjectionModel(projectionName: $projectionName, status: ProjectionStatus::IDLE->value, lockedUntil: null);
    $this->assertProjectionModelCheckpoint(projectionName: $projectionName, streamName: $streamName, position: 4);
    $this->assertProjectionReport(cycle: 1, ackedEvent: 4, totalEvent: 4, descriptionId: $descriptionId);
})->with('projection optional description id');

test('run projection again from last position kept in memory', function () {
    $this->setupProjection(
        [[$streamName = 'account', null]],
        projectionName: $projectionName = 'balance',
    );

    $this->balanceEventStore($streamName)
        ->withBalanceCreated(version: 1, amount: 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $reactors = $this->getReadModelReactor();

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($streamName)
        ->when($reactors, $this->getThenReactor())
        ->filter($this->factory->getQueryFilter())
        ->run(inBackground: false);

    $this->assertProjectionState(['total' => 100, 'events' => $this->expectedStateEvents]);
    $this->assertReadModelBalance($streamName, 100);
    $this->assertProjectionModel(projectionName: $projectionName, status: ProjectionStatus::IDLE->value, lockedUntil: null);
    $this->assertProjectionModelCheckpoint(projectionName: $projectionName, streamName: $streamName, position: 4);
    $this->assertProjectionReport(cycle: 1, ackedEvent: 4, totalEvent: 4);

    // append events to the stream
    $this->balanceEventStore($streamName)
        ->withBalanceAdded(version: 5, amount: 600)
        ->withBalanceSubtracted(version: 6, amount: 400);

    // run projection again
    $this->projector->run(inBackground: false);

    $expectedStateEvents = array_merge($this->expectedStateEvents, [BalanceAdded::class, BalanceSubtracted::class]);

    $this->assertProjectionState(['total' => 300, 'events' => $expectedStateEvents]);
    $this->assertReadModelBalance($streamName, 300);
    $this->assertProjectionModel(projectionName: $projectionName, status: ProjectionStatus::IDLE->value, lockedUntil: null);
    $this->assertProjectionModelCheckpoint(projectionName: $projectionName, streamName: $streamName, position: 6);
    $this->assertProjectionReport(cycle: 1, ackedEvent: 2, totalEvent: 2);
});

test('read model scope with one processed event', function () {
    $this->setupProjection(
        [[$streamName = 'account', null]],
        projectionName: $projectionName = 'balance',
    );

    $this->balanceEventStore($streamName)->withBalanceCreated(version: 1, amount: 100);

    $reactors = [function (BalanceCreated $event) {}];
    $thenReactor = function (ReadModelScope $scope): void {
        expect($scope->userState())->toBeNull()
            ->and($scope->streamName())->toBe('account')
            ->and($scope)->toBeInstanceOf(ReadModelScope::class)
            ->and($scope->readModel())->toBe($this->readModel)
            ->and($scope->clock())->toBeInstanceOf(Clock::class);
    };

    $this->projector
        ->subscribeToStream($streamName)
        ->when($reactors, $thenReactor)
        ->filter($this->factory->getQueryFilter())
        ->run(inBackground: false);

    expect($this->projector->getName())->toBe($projectionName);
});

test('reactors should never been called when no event is processed', function () {
    $this->setupProjection(
        [[$streamName = 'account', null]],
        projectionName: $projectionName = 'balance',
    );

    $reactors = [function (BalanceCreated $event) {
        $this->userState->upsert('total', $event->amount());
    }];

    $thenReactor = function (): void {
        throw new Exception('should never been called');
    };

    $this->projector
        ->subscribeToStream($streamName)
        ->when($reactors, $thenReactor)
        ->filter($this->factory->getQueryFilter())
        ->run(inBackground: false);

    $this->assertProjectionState([]);
    $this->assertProjectionModel(projectionName: $projectionName, status: ProjectionStatus::IDLE->value, lockedUntil: null);
});

test('does not detect gaps with no retry', function (bool $keepRunning) {
    $this->setupProjection(
        [[$streamName = 'account', null]],
        projectionName: $projectionName = 'balance',
    );

    $this->balanceEventStore($streamName)
        ->withBalanceCreated(version: 1, amount: 100)
        ->withVersioningAmount([[3, 200], [5, -150], [7, -25]]);

    $reactors = $this->getReadModelReactor();
    $thenReactor = $this->getThenReactor(keepRunning: true, stopAt: ['events', 4]);

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($streamName)
        ->when($reactors, $thenReactor)
        ->filter($this->factory->getQueryFilter())
        ->run(inBackground: $keepRunning);

    $this->assertProjectionState(['total' => 125, 'events' => $this->expectedStateEvents]);
    $this->assertReadModelBalance($streamName, 125);
    $this->assertProjectionModel(projectionName: $projectionName, status: ProjectionStatus::IDLE->value, lockedUntil: null);
    $this->assertProjectionModelCheckpoint(projectionName: $projectionName, streamName: $streamName, position: 7);
    $this->assertProjectionReport(cycle: 1, ackedEvent: 4, totalEvent: 4);
})->with('keep projection running');

test('detect gaps with setup retries and record gap', function (array $retries, bool $recordGap) {
    $this->setupProjection(
        [[$streamName = 'account', null]],
        projectionName: $projectionName = 'balance',
        options: ['retries' => $retries, 'recordGap' => $recordGap]
    );

    $this->balanceEventStore($streamName)
        ->withBalanceCreated(version: 1, amount: 100)
        ->withVersioningAmount([[3, 200], [5, -150], [10, -50]]);

    $reactors = $this->getReadModelReactor();
    $thenReactor = $this->getThenReactor(keepRunning: true, stopAt: ['events', 4]);

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($streamName)
        ->when($reactors, $thenReactor)
        ->filter($this->factory->getQueryFilter())
        ->run(inBackground: true);

    $this->assertProjectionState(['total' => 100, 'events' => $this->expectedStateEvents]);
    $this->assertReadModelBalance($streamName, 100);
    $this->assertProjectionModel(projectionName: $projectionName, status: ProjectionStatus::IDLE->value, lockedUntil: null);

    $expectedGaps = $recordGap ? [2, 4, [6, 9]] : [];
    $this->assertProjectionModelCheckpoint(projectionName: $projectionName, streamName: $streamName, position: 10, gaps: $expectedGaps);

    $expectedCycles = $this->calculateExpectedCycles(
        numberOfEventWithNoGap: 1,
        numberOfRetry: count($retries),
        numberOfEventWithGap: 3
    );

    $this->assertProjectionReport(cycle: $expectedCycles, ackedEvent: 4, totalEvent: 4);
})->with(
    'projection options with non empty retries',
    'projection options record gap'
);

test('detect larger gaps with setup retries and record gap with range threshold', function (array $retries, bool $recordGap) {
    $this->setupProjection(
        [[$streamName = 'account', null]],
        projectionName: $projectionName = 'balance',
        options: ['retries' => $retries, 'recordGap' => $recordGap]
    );

    $this->balanceEventStore($streamName)
        ->withBalanceCreated(version: 1, amount: 100)
        ->withVersioningAmount([[3, 200], [25, -150], [70, -50]]);

    $reactors = $this->getReadModelReactor();
    $thenReactor = $this->getThenReactor(keepRunning: true, stopAt: ['events', 4]);

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($streamName)
        ->when($reactors, $thenReactor)
        ->filter($this->factory->getQueryFilter())
        ->run(inBackground: true);

    $this->assertProjectionState(['total' => 100, 'events' => $this->expectedStateEvents]);
    $this->assertReadModelBalance($streamName, 100);
    $this->assertProjectionModel(projectionName: $projectionName, status: ProjectionStatus::IDLE->value, lockedUntil: null);

    $expectedGaps = $recordGap ? [2, [4, 24], [26, 69]] : [];
    $this->assertProjectionModelCheckpoint(projectionName: $projectionName, streamName: $streamName, position: 70, gaps: $expectedGaps);

    $expectedCycles = $this->calculateExpectedCycles(
        numberOfEventWithNoGap: 1,
        numberOfRetry: count($retries),
        numberOfEventWithGap: 3
    );

    $this->assertProjectionReport(cycle: $expectedCycles, ackedEvent: 4, totalEvent: 4);
})->with(
    'projection options with non empty retries',
    'projection options record gap'
);

test('fails detect gaps with running once and setup retries', function (array $retries) {
    $this->setupProjection(
        [[$streamName = 'account', null]],
        projectionName: $projectionName = 'balance',
        options: ['retries' => $retries]
    );

    $this->balanceEventStore($streamName)
        ->withBalanceCreated(version: 1, amount: 100)
        ->withBalanceAdded(version: 10, amount: 200);

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream('account')
        ->when($this->getReadModelReactor(), $this->getThenReactor())
        ->filter($this->factory->getQueryFilter())
        ->run(inBackground: false);

    $this->assertProjectionState(['total' => 100, 'events' => [BalanceCreated::class]]);
    $this->assertReadModelBalance($streamName, 100);
    $this->assertProjectionModel(projectionName: 'balance', status: ProjectionStatus::IDLE->value, lockedUntil: null);
    $this->assertProjectionModelCheckpoint(projectionName: $projectionName, streamName: $streamName, position: 1);
    $this->assertProjectionReport(cycle: 1, ackedEvent: 1, totalEvent: 1);
})->with('projection options with non empty retries');

test('called [then] callback even when stream event is not acknowledged', function () {
    $this->setupProjection(
        [[$streamName = 'account', null]],
        projectionName: 'balance'
    );

    $this->balanceEventStore($streamName)
        ->withBalanceCreated(version: 1, amount: 100)
        ->withBalanceNoOp(version: 2);

    $reactors = [
        function (BalanceCreated $event) {},
        function (SomeEvent $event) {
            throw new Exception('Event is not part of the stream');
        },
    ];

    $thenReactor = function (ReadModelScope $scope): void {
        $scope->userState()->increment('then called');
    };

    $this->projector
        ->initialize(fn (): array => ['then called' => 0])
        ->subscribeToStream($streamName)
        ->when($reactors, $thenReactor)
        ->filter($this->factory->getQueryFilter())
        ->run(inBackground: false);

    $this->assertProjectionState(['then called' => 2]);
});

test('subscribe to all stream', function () {
    $this->setupProjection(
        [
            [$a1 = 'account1', null],
            [$a2 = 'account2', null],
        ],
        projectionName: 'balance',
    );

    $this->balanceEventStore($a1)
        ->withBalanceCreated(version: 1, amount: 100)
        ->withVersioningAmount([[2, 200], [3, -100], [4, -100]]);

    $this->balanceEventStore($a2)
        ->withBalanceCreated(version: 1, amount: 600)
        ->withVersioningAmount([[2, 100], [3, -100], [4, -100]]);

    $this->assertStreamExists($a1, true);
    $this->assertStreamExists($a2, true);

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToAll()
        ->when($this->getReadModelReactor(), $this->getThenReactor())
        ->filter($this->factory->getQueryFilter())
        ->run(inBackground: false);

    $this->assertPartialProjectionState('total', 600);
    expect($this->projector->getState()['events'])->toHaveCount(8);

    $this->assertReadModelBalance($a1, 100);
    $this->assertReadModelBalance($a2, 500);
    $this->assertProjectionReport(cycle: 1, ackedEvent: 8, totalEvent: 8);
});

test('load stream events with block size and load limiter', function () {
    $this->setupProjection(
        [[$accountOne = 'account_one', null]],
        projectionName: 'balance',
        options: ['blockSize' => 100, 'loadLimiter' => 100],
    );

    $this->balanceEventStore($accountOne)->make(1000);
    $this->assertStreamExists($accountOne, true);

    $thenReactor = function (ReadModelScope $scope): void {
        $scope->userState()->push('events', $scope->event()::class);

        if (count($scope->userState()['events']) === 1000) {
            $scope->stop();
        }
    };

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToAll()
        ->when($this->getReadModelReactor(), $thenReactor)
        ->filter($this->factory->getQueryFilter())
        ->run(inBackground: true);

    expect($this->projector->getState()['events'])->toHaveCount(1000);

    $expectedCycles = 1000 / 100; // load limiter divided by block size (no gaps)
    $this->assertProjectionReport(cycle: $expectedCycles, ackedEvent: 1000, totalEvent: 1000);
});

test('stop projection from projector', function () {
    $this->setupProjection(
        [[$accountOne = 'account_one', null]],
        projectionName: 'balance',
        options: ['signal' => true],
    );

    $this->balanceEventStore($accountOne)->make(10);
    $this->assertStreamExists($accountOne, true);

    $projector = $this->projector;
    $thenReactor = function (ReadModelScope $scope) use ($projector): void {
        $scope->userState()->push('events', $scope->event()::class);
        if (count($scope->userState()['events']) === 10) {
            $projector->stop();
        }
    };

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToAll()
        ->when($this->getReadModelReactor(), $thenReactor)
        ->filter($this->factory->getQueryFilter())
        ->run(inBackground: true);

    expect($this->projector->getState()['events'])->toHaveCount(10);

    $this->assertProjectionReport(cycle: 1, ackedEvent: 10, totalEvent: 10);
});
