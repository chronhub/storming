<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector;

use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Projector\QueryProjectorScope;
use Storm\Projector\Scope\EventScope;
use Storm\Projector\Scope\UserStateScope;
use Storm\Tests\Domain\Balance\BalanceAdded;
use Storm\Tests\Domain\Balance\BalanceCreated;
use Storm\Tests\Domain\Balance\BalanceSubtracted;
use Storm\Tests\Feature\Projector\InMemory\Concern\InMemoryProjectionExpectationTrait;
use Storm\Tests\Feature\Projector\InMemory\Concern\InMemoryQueryProjectionTestBaseTrait;
use Storm\Tests\Feature\Projector\InMemory\InMemoryTestingFactory;

use function array_merge;

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

dataset('description id', [null, 'describe the projection with a custom id']);

test('query projection', function (?string $descriptionId) {
    $this->setupProjection(descriptionId: $descriptionId);

    $streamName = 'balance_one';
    $this->setupBalanceOne($streamName);

    $this->balanceOneEventStore
        ->withBalanceCreated(1, 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $this->projector
        ->initialize(fn (): array => [])
        ->subscribeToStream($streamName)
        ->when($this->getQueryReactor())
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(false);

    $this->assertProjectionState(
        [
            'balances' => [$this->balanceOne->toString() => 100],
            'events' => $this->expectedStateEvents,
        ]
    );

    $this->assertProjectionReport(cycle: 1, ackedEvent: 4, totalEvent: 4, descriptionId: $descriptionId);
})->with('description id');

test('query projection with many streams', function () {
    $this->setupProjection();

    $streamOne = 'balance_one';
    $this->setupBalanceOne($streamOne);
    $this->balanceOneEventStore
        ->withBalanceCreated(1, 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $streamTwo = 'balance_two';
    $this->setupBalanceTwo($streamTwo);
    $this->balanceTwoEventStore
        ->withBalanceCreated(1, 500)
        ->withVersioningAmount([[2, 100], [3, -50], [4, -25]]);

    $this->projector
        ->initialize(fn (): array => [])
        ->subscribeToStream($streamOne, $streamTwo)
        ->when($this->getQueryReactor())
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(false);

    $this->assertProjectionState(
        [
            'balances' => [
                $this->balanceOne->toString() => 100,
                $this->balanceTwo->toString() => 525,
            ],
            'events' => array_merge($this->expectedStateEvents, $this->expectedStateEvents),
        ]
    );

    $this->assertProjectionReport(cycle: 1, ackedEvent: 8, totalEvent: 8);
});

test('query projection from stream partition', function () {
    $this->setupProjection();

    $streamOne = 'balance-one';
    $this->setupBalanceOne($streamOne);
    $this->balanceOneEventStore
        ->withBalanceCreated(1, 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $streamTwo = 'balance-two';
    $this->setupBalanceTwo($streamTwo);
    $this->balanceTwoEventStore
        ->withBalanceCreated(1, 500)
        ->withVersioningAmount([[2, 100], [3, -50], [4, -25]]);

    $this->assertStreamExists($streamOne, true);
    $this->assertStreamExists($streamTwo, true);

    $this->projector
        ->initialize(fn (): array => [])
        ->subscribeToPartition('balance')
        ->when($this->getQueryReactor())
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(false);

    $this->assertProjectionState(
        [
            'balances' => [
                $this->balanceOne->toString() => 100,
                $this->balanceTwo->toString() => 525,
            ],
            'events' => array_merge($this->expectedStateEvents, $this->expectedStateEvents),
        ]
    );

    $this->assertProjectionReport(cycle: 1, ackedEvent: 8, totalEvent: 8);
});

test('stop query projection from query scope', function () {
    $this->setupProjection();

    $streamName = 'balance_one';
    $this->setupBalanceOne($streamName);

    $this->balanceOneEventStore
        ->withBalanceCreated(1, 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $this->projector
        ->initialize(fn (): array => [])
        ->subscribeToStream($streamName)
        ->when($this->getQueryReactor(keepRunning: true, stopAt: ['events', 2]))
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(true);

    $this->assertProjectionState(
        [
            'balances' => [$this->balanceOne->toString() => 300],
            'events' => [BalanceCreated::class, BalanceAdded::class],
        ]
    );

    $this->assertProjectionReport(cycle: 1, ackedEvent: 2, totalEvent: 2);
});

test('query projection with a custom query filter', function () {
    $this->setupProjection();

    $streamName = 'balance_one';
    $this->setupBalanceOne($streamName);
    $this->balanceOneEventStore
        ->withBalanceCreated(1, 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $customQueryFilter = $this->customFilterQueryEvents(BalanceSubtracted::class);

    $this->projector
        ->initialize(fn (): array => [])
        ->subscribeToStream($streamName)
        ->when($this->getQueryReactor())
        ->filter($customQueryFilter)
        ->run(false);

    $this->assertProjectionState(
        [
            'balances' => [$this->balanceOne->toString() => -200],
            'events' => [BalanceSubtracted::class, BalanceSubtracted::class],
        ]
    );

    $this->assertProjectionReport(cycle: 1, ackedEvent: 2, totalEvent: 2);
});

test('query projection running in background and stop projection from event scope', function () {
    $this->setupProjection();

    $streamName = 'balance_one';
    $this->setupBalanceOne($streamName);

    $this->balanceOneEventStore
        ->withBalanceCreated(1, 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $reactors = function (EventScope $eventScope): void {
        $callback = function (DomainEvent $event, QueryProjectorScope $scope, UserStateScope $userState): void {
            $balanceId = $event->toContent()['id'];

            if ($event instanceof BalanceCreated || $event instanceof BalanceAdded) {
                $userState->increment('balances.'.$balanceId, $event->amount());
            }
        };

        $eventScope->ack(BalanceCreated::class)?->then($callback);
        $eventScope->ack(BalanceAdded::class)?->then($callback);

        if ($eventScope->match(BalanceSubtracted::class)) {
            $eventScope->projector->stop();
        }
    };

    $this->projector
        ->initialize(fn (): array => [])
        ->subscribeToStream($streamName)
        ->when($reactors)
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(true);

    $this->assertProjectionState(['balances' => [$this->balanceOne->toString() => 300]]);

    $this->assertProjectionReport(cycle: 1, ackedEvent: 2, totalEvent: 3);
});

/**
 * The only difference is the stop() call inside the then() callback
 * will increment the acked event count
 */
test('query projection running in background and stop projection from event scope 2', function () {
    $this->setupProjection();

    $streamName = 'balance_one';
    $this->setupBalanceOne($streamName);

    $this->balanceOneEventStore
        ->withBalanceCreated(1, 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $reactors = function (EventScope $eventScope): void {
        $callback = function (DomainEvent $event, QueryProjectorScope $scope, UserStateScope $userState): void {
            $balanceId = $event->toContent()['id'];

            if ($event instanceof BalanceCreated || $event instanceof BalanceAdded) {
                $userState->increment('balances.'.$balanceId, $event->amount());
            }

            if ($event instanceof BalanceSubtracted) {
                $scope->stop();
            }
        };

        $eventScope->ack(BalanceCreated::class)?->then($callback);
        $eventScope->ack(BalanceAdded::class)?->then($callback);
        $eventScope->ack(BalanceSubtracted::class)?->then($callback);
    };

    $this->projector
        ->initialize(fn (): array => [])
        ->subscribeToStream($streamName)
        ->when($reactors)
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(true);

    $this->assertProjectionState(['balances' => [$this->balanceOne->toString() => 300]]);

    $this->assertProjectionReport(cycle: 1, ackedEvent: 3, totalEvent: 3);
});

test('query projection running once and does not stop on gap', function () {
    $this->setupProjection();

    $streamName = 'balance_one';
    $this->setupBalanceOne($streamName);

    $this->balanceOneEventStore
        ->withBalanceCreated(1, 100)
        ->withVersioningAmount([[5, 200], [20, -150], [50, -50]]);

    $this->projector
        ->initialize(fn (): array => [])
        ->subscribeToStream($streamName)
        ->when($this->getQueryReactor())
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(false);

    $this->assertProjectionState(
        [
            'balances' => [$this->balanceOne->toString() => 100],
            'events' => $this->expectedStateEvents,
        ]
    );

    $this->assertProjectionReport(cycle: 1, ackedEvent: 4, totalEvent: 4);
});

test('query projection running once and stop on gap when retries are configured', function () {
    $this->setupProjection(options: ['retries' => [1, 2, 3]]);

    $streamName = 'balance_one';
    $this->setupBalanceOne($streamName);

    $this->balanceOneEventStore
        ->withBalanceCreated(1, 600)
        ->withBalanceAdded(3, 200);

    $this->projector
        ->initialize(fn (): array => [])
        ->subscribeToStream($streamName)
        ->when($this->getQueryReactor())
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(false);

    $this->assertProjectionState(
        [
            'balances' => [$this->balanceOne->toString() => 600],
            'events' => [BalanceCreated::class],
        ]
    );

    $this->assertProjectionReport(cycle: 1, ackedEvent: 1, totalEvent: 1);
});

// fixMe issue with checkpoints, stuck at the first event
// useful for live query projection
test('query projection running in background and resolve gap when retries are configured', function () {
    $this->setupProjection(options: ['retries' => [1, 2, 3]]);

    $streamName = 'balance_one';
    $this->setupBalanceOne($streamName);

    $this->balanceOneEventStore
        ->withBalanceCreated(1, 600)
        ->withBalanceAdded(3, 200)
        ->withBalanceSubtracted(10, 50);

    $this->projector
        ->initialize(fn (): array => [])
        ->subscribeToStream($streamName)
        ->when($this->getQueryReactor(keepRunning: true, stopAt: ['events', 3]))
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(true);

    $this->assertProjectionState(
        [
            'balances' => [$this->balanceOne->toString() => 650],
            'events' => [BalanceCreated::class, BalanceAdded::class, BalanceSubtracted::class],
        ]
    );

    $this->assertProjectionReport(cycle: 1, ackedEvent: 3, totalEvent: 3);
})->todo();
