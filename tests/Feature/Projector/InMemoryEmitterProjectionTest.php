<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector;

use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Projector\EmitterScope;
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

test('emit event', function () {
    $manager = $this->factory->createProjectorManager();

    expect($this->factory->projectionProvider->exists('projection_balance'))->toBeFalse()
        ->and($this->factory->chronicler->hasStream(new StreamName('projection_balance')))->toBeFalse();

    $balanceId = BalanceId::create();
    $streamName = new StreamName('balance');
    $store = new BalanceEventStore($this->factory->chronicler, $streamName, $balanceId);
    $store
        ->withBalanceCreated(1, 100)
        ->withBalanceAdded(2, 200)
        ->withBalanceSubtracted(3, 150)
        ->withBalanceSubtracted(4, 50);

    $projector = $manager->newEmitterProjector('projection_balance');

    $reactors = function (EventScope $scope): void {
        $scope
            ->ackOneOf(BalanceCreated::class, BalanceAdded::class, BalanceSubtracted::class)
            ->then(function (DomainEvent $event, EmitterScope $scope, UserStateScope $userState): void {
                if ($event instanceof BalanceCreated || $event instanceof BalanceAdded) {
                    $userState->increment('balance', $event->amount());
                }

                if ($event instanceof BalanceSubtracted) {
                    $userState->decrement('balance', $event->amount());
                }

                $scope->emit($event);
            });
    };

    $projector
        ->initialize(fn (): array => ['balance' => 0])
        ->subscribeToStream('balance')
        ->when($reactors)
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(false);

    expect($this->factory->projectionProvider->exists('projection_balance'))->toBeTrue()
        ->and($this->factory->chronicler->hasStream(new StreamName('projection_balance')))->toBeTrue();
});

test('link event to new stream', function () {
    $manager = $this->factory->createProjectorManager();

    expect($this->factory->projectionProvider->exists('projection_balance'))->toBeFalse()
        ->and($this->factory->chronicler->hasStream(new StreamName('projection_balance')))->toBeFalse()
        ->and($this->factory->chronicler->hasStream(new StreamName('linked_balance')))->toBeFalse();

    $balanceId = BalanceId::create();
    $streamName = new StreamName('balance');
    $store = new BalanceEventStore($this->factory->chronicler, $streamName, $balanceId);
    $store
        ->withBalanceCreated(1, 100)
        ->withBalanceAdded(2, 200)
        ->withBalanceSubtracted(3, 150)
        ->withBalanceSubtracted(4, 50);

    $projector = $manager->newEmitterProjector('projection_balance');

    $reactors = function (EventScope $scope): void {
        $scope
            ->ackOneOf(BalanceCreated::class, BalanceAdded::class, BalanceSubtracted::class)
            ->then(function (DomainEvent $event, EmitterScope $scope, UserStateScope $userState): void {
                if ($event instanceof BalanceCreated || $event instanceof BalanceAdded) {
                    $userState->increment('balance', $event->amount());
                }

                if ($event instanceof BalanceSubtracted) {
                    $userState->decrement('balance', $event->amount());
                }

                $scope->linkTo('linked_projection', $event);
            });
    };

    $projector
        ->initialize(fn (): array => ['balance' => 0])
        ->subscribeToStream('balance')
        ->when($reactors)
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(false);

    expect($this->factory->projectionProvider->exists('projection_balance'))->toBeTrue()
        ->and($this->factory->chronicler->hasStream(new StreamName('projection_balance')))->toBeFalse()
        ->and($this->factory->chronicler->hasStream(new StreamName('linked_projection')))->toBeTrue();
});
