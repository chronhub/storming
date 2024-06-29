<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector;

use Storm\Chronicler\Direction;
use Storm\Contract\Chronicler\InMemoryQueryFilter;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Projector\QueryProjectorScope;
use Storm\Projector\Scope\EventScope;
use Storm\Projector\Scope\UserStateScope;
use Storm\Stream\StreamName;
use Storm\Tests\Domain\Balance\BalanceAdded;
use Storm\Tests\Domain\Balance\BalanceCreated;
use Storm\Tests\Domain\Balance\BalanceId;
use Storm\Tests\Domain\Balance\BalanceSubtracted;
use Storm\Tests\Domain\BalanceEventStore;
use Storm\Tests\Feature\InMemoryTestingFactory;

beforeEach(function () {
    $this->factory = new InMemoryTestingFactory();
});

test('query projection', function () {
    $manager = $this->factory->createProjectorManager();

    $balanceId = BalanceId::create();
    $streamName = new StreamName('balance');
    $store = new BalanceEventStore($this->factory->chronicler, $streamName, $balanceId);
    $store
        ->withBalanceCreated(1, 100)
        ->withBalanceAdded(2, 200)
        ->withBalanceSubtracted(3, 150)
        ->withBalanceSubtracted(4, 50);

    $projector = $manager->newQueryProjector();

    $reactors = function (EventScope $scope): void {
        $scope
            ->ackOneOf(BalanceCreated::class, BalanceAdded::class, BalanceSubtracted::class)
            ->then(function (DomainEvent $event, QueryProjectorScope $scope, UserStateScope $userState): void {
                if ($event instanceof BalanceCreated || $event instanceof BalanceAdded) {
                    $userState->increment('balance', $event->amount());
                }

                if ($event instanceof BalanceSubtracted) {
                    $userState->decrement('balance', $event->amount());
                }

                $userState->merge('events', [$event::class]);
            });
    };

    $projector
        ->initialize(fn (): array => ['balance' => 0])
        ->subscribeToStream('balance')
        ->when($reactors)
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(false);

    expect($projector->getState())->toBe([
        'balance' => 100,
        'events' => [
            BalanceCreated::class,
            BalanceAdded::class,
            BalanceSubtracted::class,
            BalanceSubtracted::class,
        ],
    ])->and($this->factory->projectionProvider->exists('balance'))->toBeFalse();
});

test('query projection with two streams', function () {
    $manager = $this->factory->createProjectorManager();

    $balanceIdOne = BalanceId::create();
    $balanceOne = new BalanceEventStore($this->factory->chronicler, new StreamName('balance_one'), $balanceIdOne);
    $balanceOne
        ->withBalanceCreated(1, 100)
        ->withBalanceAdded(2, 200)
        ->withBalanceSubtracted(3, 150)
        ->withBalanceSubtracted(4, 50);

    $balanceIdTwo = BalanceId::create();
    $balanceTwo = new BalanceEventStore($this->factory->chronicler, new StreamName('balance_two'), $balanceIdTwo);
    $balanceTwo
        ->withBalanceCreated(1)
        ->withBalanceAdded(2, 100)
        ->withBalanceSubtracted(3, 50)
        ->withBalanceSubtracted(4, 25);

    $projector = $manager->newQueryProjector();

    $reactors = function (EventScope $scope): void {
        $scope
            ->ackOneOf(BalanceCreated::class, BalanceAdded::class, BalanceSubtracted::class)
            ->then(function (DomainEvent $event, QueryProjectorScope $scope, UserStateScope $userState): void {
                $balanceId = $event->toContent()['id'];

                if ($event instanceof BalanceCreated) {
                    $userState[$balanceId] = $event->amount();
                }

                if ($event instanceof BalanceAdded) {
                    $userState[$balanceId] += $event->amount();
                }

                if ($event instanceof BalanceSubtracted) {
                    $userState[$balanceId] -= $event->amount();
                }
            });
    };

    $projector
        ->initialize(fn (): array => [])
        ->subscribeToStream('balance_one', 'balance_two')
        ->when($reactors)
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(false);

    expect($projector->getState())->toBe([
        $balanceIdOne->toString() => 100,
        $balanceIdTwo->toString() => 25,
    ]);
});

test('query projection with category', function () {
    $manager = $this->factory->createProjectorManager();

    $balanceIdOne = BalanceId::create();
    $balanceOne = new BalanceEventStore($this->factory->chronicler, new StreamName('balance-one'), $balanceIdOne);
    $balanceOne
        ->withBalanceCreated(1, 100)
        ->withBalanceAdded(2, 200)
        ->withBalanceSubtracted(3, 150)
        ->withBalanceSubtracted(4, 50);

    $balanceIdTwo = BalanceId::create();
    $balanceTwo = new BalanceEventStore($this->factory->chronicler, new StreamName('balance-two'), $balanceIdTwo);
    $balanceTwo
        ->withBalanceCreated(1)
        ->withBalanceAdded(2, 100)
        ->withBalanceSubtracted(3, 50)
        ->withBalanceSubtracted(4, 25);

    $projector = $manager->newQueryProjector();

    $reactors = function (EventScope $scope): void {
        $scope
            ->ackOneOf(BalanceCreated::class, BalanceAdded::class, BalanceSubtracted::class)
            ->then(function (DomainEvent $event, QueryProjectorScope $scope, UserStateScope $userState): void {
                if ($event instanceof BalanceCreated) {
                    $userState[$scope->streamName()] = $event->amount();
                }

                if ($event instanceof BalanceAdded) {
                    $userState[$scope->streamName()] += $event->amount();
                }

                if ($event instanceof BalanceSubtracted) {
                    $userState[$scope->streamName()] -= $event->amount();
                }
            });
    };

    $projector
        ->initialize(fn (): array => [])
        ->subscribeToCategory('balance')
        ->when($reactors)
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(false);

    expect($projector->getState())->toBe([
        'balance-one' => 100,
        'balance-two' => 25,
    ]);
});

test('stop query projection', function () {
    $manager = $this->factory->createProjectorManager();

    $balanceId = BalanceId::create();
    $streamName = new StreamName('balance');
    $store = new BalanceEventStore($this->factory->chronicler, $streamName, $balanceId);
    $store
        ->withBalanceCreated(1, 100)
        ->withBalanceAdded(2, 200)
        ->withBalanceSubtracted(3, 150)
        ->withBalanceSubtracted(4, 50);

    $projector = $manager->newQueryProjector();

    $reactors = function (EventScope $scope): void {
        $scope
            ->ackOneOf(BalanceCreated::class, BalanceAdded::class, BalanceSubtracted::class)
            ->then(function (DomainEvent $event, QueryProjectorScope $scope, UserStateScope $userState): void {
                $userState
                    ->increment()
                    ->increment('balance', $event->toContent()['amount'])
                    ->merge('events', [$event::class]);
            });

        if ($scope->userState['count'] === 2) {
            $scope->projector->stop();
        }
    };

    $projector
        ->initialize(fn (): array => ['count' => 0, 'balance' => 0])
        ->subscribeToStream('balance')
        ->when($reactors)
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(true);

    expect($projector->getState())->toBe([
        'count' => 2,
        'balance' => 300,
        'events' => [
            BalanceCreated::class,
            BalanceAdded::class,
        ],
    ]);
});

test('query projection with query filter and induce gap', function () {
    $queryFilter = new class() implements InMemoryQueryFilter
    {
        public function orderBy(): Direction
        {
            return Direction::FORWARD;
        }

        public function apply(): callable
        {
            return fn (DomainEvent $event): bool => $event instanceof BalanceSubtracted;
        }
    };

    $manager = $this->factory->createProjectorManager();

    $balanceId = BalanceId::create();
    $streamName = new StreamName('balance');
    $store = new BalanceEventStore($this->factory->chronicler, $streamName, $balanceId);
    $store
        ->withBalanceCreated(1, 100)
        ->withBalanceAdded(2, 200)
        ->withBalanceSubtracted(3, 150)
        ->withBalanceSubtracted(4, 50)
        ->withBalanceSubtracted(5, 10);

    $projector = $manager->newQueryProjector();

    $reactors = function (EventScope $scope): void {
        $scope
            ->ack(BalanceSubtracted::class)
            ?->then(function (DomainEvent $event, QueryProjectorScope $scope, UserStateScope $userState): void {
                $userState
                    ->increment()
                    ->merge('events', [$event::class]);
            });
    };

    $projector
        ->initialize(fn (): array => ['count' => 0])
        ->subscribeToStream('balance')
        ->when($reactors)
        ->filter($queryFilter)
        ->run(false);

    expect($projector->getState())->toBe([
        'count' => 3,
        'events' => [
            BalanceSubtracted::class,
            BalanceSubtracted::class,
            BalanceSubtracted::class,
        ],
    ]);
});
