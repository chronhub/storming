<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector;

use Storm\Chronicler\Direction;
use Storm\Contract\Chronicler\InMemoryQueryFilter;
use Storm\Contract\Message\DomainEvent;
use Storm\Stream\StreamName;
use Storm\Tests\Domain\Balance\BalanceAdded;
use Storm\Tests\Domain\Balance\BalanceCreated;
use Storm\Tests\Domain\Balance\BalanceId;
use Storm\Tests\Domain\Balance\BalanceSubtracted;
use Storm\Tests\Domain\BalanceEventStore;
use Storm\Tests\Domain\ProjectionBalanceReactor;
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
    $reactors = ProjectionBalanceReactor::getQueryReactors(false, false);

    $projector
        ->initialize(fn (): array => [])
        ->subscribeToStream('balance')
        ->when($reactors)
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(false);

    expect($projector->getState())->toBe([
        $balanceId->toString() => 100,
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
    $reactors = ProjectionBalanceReactor::getQueryReactors(false, false);

    $projector
        ->initialize(fn (): array => [])
        ->subscribeToStream('balance_one', 'balance_two')
        ->when($reactors)
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(false);

    expect($projector->getState()[$balanceIdOne->toString()])->toBe(100)
        ->and($projector->getState()[$balanceIdTwo->toString()])->toBe(25);
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

    $reactors = ProjectionBalanceReactor::getQueryReactors(false, false);

    $projector
        ->initialize(fn (): array => [])
        ->subscribeToCategory('balance')
        ->when($reactors)
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(false);

    expect($projector->getState()[$balanceIdOne->toString()])->toBe(100)
        ->and($projector->getState()[$balanceIdTwo->toString()])->toBe(25);
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
    $reactors = ProjectionBalanceReactor::getQueryReactors(true, 2);

    $projector
        ->initialize(fn (): array => [])
        ->subscribeToStream('balance')
        ->when($reactors)
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(true);

    expect($projector->getState()['events'])->toBe([
        BalanceCreated::class,
        BalanceAdded::class,
    ])->and($projector->getState()[$balanceId->toString()])->toBe(300);
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
    $reactors = ProjectionBalanceReactor::getQueryReactors(false, false);

    $projector
        ->initialize(fn (): array => [])
        ->subscribeToStream('balance')
        ->when($reactors)
        ->filter($queryFilter)
        ->run(false);

    expect($projector->getState()['events'])->toBe([
        BalanceSubtracted::class,
        BalanceSubtracted::class,
        BalanceSubtracted::class,
    ])->and($projector->getState()[$balanceId->toString()])->toBe(-210);
});
