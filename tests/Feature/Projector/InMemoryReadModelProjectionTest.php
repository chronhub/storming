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
use Storm\Projector\Workflow\Notification\Checkpoint\GapDetected;
use Storm\Stream\StreamName;
use Storm\Tests\Domain\Balance\BalanceAdded;
use Storm\Tests\Domain\Balance\BalanceCreated;
use Storm\Tests\Domain\Balance\BalanceId;
use Storm\Tests\Domain\Balance\BalanceSubtracted;
use Storm\Tests\Domain\BalanceEventStore;
use Storm\Tests\Feature\InMemoryTestingFactory;

use function count;

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
        ->and($projection->state())->toBe($this->factory->serializer->encode($state, 'json'))
        ->and($projection->status())->toBe(ProjectionStatus::IDLE->value)
        ->and($projection->lockedUntil())->toBeNull();

    // assert checkpoint
    $checkpoint = $this->factory->serializer->decode($projection->checkpoint(), 'json');
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

test('detect gaps with running in background or once and no retry', function (bool $keepRunning) {
    $manager = $this->factory->createProjectorManager();

    $balanceId = BalanceId::create();
    $streamName = new StreamName('balance');
    $store = new BalanceEventStore($this->factory->chronicler, $streamName, $balanceId);
    $store
        ->withBalanceCreated(1, 100)
        ->withBalanceAdded(3, 200)
        ->withBalanceSubtracted(5, 150)
        ->withBalanceSubtracted(7, 50);

    $projector = $manager->newReadModelProjector($streamName->name, $this->readModel, ['retries' => []]);

    $reactors = function (EventScope $scope) use ($keepRunning): void {
        $scope
            ->ackOneOf(BalanceCreated::class, BalanceAdded::class, BalanceSubtracted::class)
            ->then(function (DomainEvent $event, ReadModelScope $scope, UserStateScope $userState) use ($keepRunning): void {
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

                if ($keepRunning) {
                    if (count($userState['events']) === 4) {
                        $scope->stop();
                    }
                }
            });
    };

    $projector
        ->initialize(fn (): array => ['balance' => 0])
        ->subscribeToStream('balance')
        ->when($reactors)
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run($keepRunning);

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
        ->and($projection->state())->toBe($this->factory->serializer->encode($state, 'json'))
        ->and($projection->status())->toBe(ProjectionStatus::IDLE->value)
        ->and($projection->lockedUntil())->toBeNull();

    // assert checkpoint
    $checkpoint = $this->factory->serializer->decode($projection->checkpoint(), 'json');
    $balanceCheckpoint = $checkpoint['balance'];

    expect($balanceCheckpoint['stream_name'])->toBe('balance')
        ->and($balanceCheckpoint['position'])->toBe(7)
        ->and($balanceCheckpoint['event_time'])->toBeString()
        ->and($balanceCheckpoint['created_at'])->toBeString()
        ->and($balanceCheckpoint['gaps'])->toBeArray()
        ->and($balanceCheckpoint['gaps'])->toBe([2, 4, 6])
        ->and($balanceCheckpoint['gap_type'])->toBe(GapDetected::class);

    // assert the read model
    $readModel = $this->readModel;
    expect($readModel->getContainer())->toBe([
        $balanceId->toString() => ['balance' => 100],
    ]);
})->with([['keep running' => true], ['run once' => false]]);

test('detect gaps with running in background and setup retries', function (array $retries) {
    $manager = $this->factory->createProjectorManager();

    $balanceId = BalanceId::create();
    $streamName = new StreamName('balance');
    $store = new BalanceEventStore($this->factory->chronicler, $streamName, $balanceId);
    $store
        ->withBalanceCreated(1, 100)
        ->withBalanceAdded(3, 200)
        ->withBalanceSubtracted(5, 150)
        ->withBalanceSubtracted(7, 50);

    $projector = $manager->newReadModelProjector($streamName->name, $this->readModel, ['retries' => $retries]);

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

                if (count($userState['events']) === 4) {
                    $scope->stop();
                }
            });
    };

    $projector
        ->initialize(fn (): array => ['balance' => 0])
        ->subscribeToStream('balance')
        ->when($reactors)
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(true);

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
        ->and($projection->state())->toBe($this->factory->serializer->encode($state, 'json'))
        ->and($projection->status())->toBe(ProjectionStatus::IDLE->value)
        ->and($projection->lockedUntil())->toBeNull();

    // assert checkpoint
    $checkpoint = $this->factory->serializer->decode($projection->checkpoint(), 'json');
    $balanceCheckpoint = $checkpoint['balance'];

    expect($balanceCheckpoint['stream_name'])->toBe('balance')
        ->and($balanceCheckpoint['position'])->toBe(7)
        ->and($balanceCheckpoint['event_time'])->toBeString()
        ->and($balanceCheckpoint['created_at'])->toBeString()
        ->and($balanceCheckpoint['gaps'])->toBeArray()
        ->and($balanceCheckpoint['gaps'])->toBe([2, 4, 6])
        ->and($balanceCheckpoint['gap_type'])->toBe(GapDetected::class);

    // assert the read model
    $readModel = $this->readModel;
    expect($readModel->getContainer())->toBe([
        $balanceId->toString() => ['balance' => 100],
    ]);
})
    ->with([['one retry' => [1]], ['two retries' => [1, 2]], ['three retries' => [1, 2, 3]]]);

/**
 * when retry is set, the projector will retry the gap detection on the next run.
 * and the projector will stop as it runs only once.
 */
test('fails detect gaps with running once and setup retries', function (array $retries) {
    $manager = $this->factory->createProjectorManager();

    $balanceId = BalanceId::create();
    $streamName = new StreamName('balance');
    $store = new BalanceEventStore($this->factory->chronicler, $streamName, $balanceId);
    $store
        ->withBalanceCreated(1, 100)
        ->withBalanceAdded(3, 200)
        ->withBalanceSubtracted(5, 150)
        ->withBalanceSubtracted(7, 50);

    $projector = $manager->newReadModelProjector($streamName->name, $this->readModel, ['retries' => $retries]);

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

    expect($state)->toBe([
        'balance' => 100,
        'events' => [BalanceCreated::class], // version 1
    ]);
})->with([['one retry' => [1]], ['two retries' => [1, 2]], ['three retries' => [1, 2, 3]]]);
