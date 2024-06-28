<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector;

use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Projector\ProjectionModel;
use Storm\Contract\Projector\ReadModelScope;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Scope\EventScope;
use Storm\Projector\Scope\UserStateScope;
use Storm\Projector\Support\ReadModel\InMemoryReadModel;
use Storm\Stream\StreamName;
use Storm\Tests\Domain\Balance\BalanceAdded;
use Storm\Tests\Domain\Balance\BalanceCreated;
use Storm\Tests\Domain\Balance\BalanceId;
use Storm\Tests\Domain\Balance\BalanceSubtracted;
use Storm\Tests\Domain\BalanceEventStore;
use Storm\Tests\Feature\InMemoryTestingFactory;

use function json_decode;
use function json_encode;

beforeEach(function () {
    $this->factory = new InMemoryTestingFactory();
    $this->readModel = new InMemoryReadModel();
});

test('read model projection', function () {
    $manager = $this->factory->createProjectorManager();

    $balanceId = BalanceId::create();
    $streamName = new StreamName('balance');
    $store = new BalanceEventStore($this->factory->chronicler, $streamName, $balanceId);
    $store
        ->withBalanceCreated(1, 100)
        ->withBalanceAdded(2, 200)
        ->withBalanceSubtracted(3, 150)
        ->withBalanceSubtracted(4, 50);

    $projector = $manager->newReadModelProjector($streamName->name, $this->readModel);

    $reactors = function (EventScope $scope): void {
        $scope
            ->ackOneOf(BalanceCreated::class, BalanceAdded::class, BalanceSubtracted::class)
            ->then(function (DomainEvent $event, ReadModelScope $scope, UserStateScope $userState): void {
                $id = $event->toContent()['id'];

                if ($event instanceof BalanceCreated) {
                    $userState->increment('balance', $event->amount());
                    $scope->stack('insert', $id, ['balance' => $event->amount()]);
                }

                if ($event instanceof BalanceAdded) {
                    $userState->increment('balance', $event->amount());
                    $scope->stack('increment', $id, 'balance', $event->amount());
                }

                if ($event instanceof BalanceSubtracted) {
                    $userState->decrement('balance', $event->amount());
                    $scope->stack('decrement', $id, 'balance', $event->amount());
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

    $state = $projector->getState();

    // assert projection state
    expect($state)->toBe([
        'balance' => 100,
        'events' => [
            BalanceCreated::class,
            BalanceAdded::class,
            BalanceSubtracted::class,
            BalanceSubtracted::class,
        ],
    ]);

    // assert projection provider
    $projectionProvider = $this->factory->projectionProvider;

    expect($projectionProvider->exists('balance'))->toBeTrue();

    $projection = $projectionProvider->retrieve('balance');

    expect($projection)->toBeInstanceOf(ProjectionModel::class)
        ->and($projection->name())->toBe('balance')
        ->and($projection->state())->toBe(json_encode($state))
        ->and($projection->status())->toBe(ProjectionStatus::IDLE->value)
        ->and($projection->lockedUntil())->toBeNull();

    // assert checkpoint
    $checkpoint = json_decode($projection->checkpoint(), true);
    $balanceCheckpoint = $checkpoint['balance'];

    expect($balanceCheckpoint['stream_name'])->toBe('balance')
        ->and($balanceCheckpoint['position'])->toBe(4)
        ->and($balanceCheckpoint['event_time'])->toBeString()
        ->and($balanceCheckpoint['created_at'])->toBeString()
        ->and($balanceCheckpoint['gaps'])->toBeArray()
        ->and($balanceCheckpoint['gaps'])->toBeEmpty()
        ->and($balanceCheckpoint['gap_type'])->toBeNull();

    // assert the read model
    $readModel = $this->readModel;
    expect($readModel->getContainer())->toBe([
        $balanceId->toString() => ['balance' => 100],
    ]);
});
