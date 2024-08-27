<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\InMemory\Support;

use Storm\Contract\Projector\ReadModelProjector;
use Storm\Projector\Connector\ConnectorManager;
use Storm\Projector\Scope\ReadModelScope;
use Storm\Projector\Stream\Filter\InMemoryFromToPosition;
use Storm\Projector\Support\Builder\ReadModelProjectorBuilder;
use Storm\Projector\Support\ReadModel\InMemoryReadModel;
use Storm\Stream\StreamName;
use Storm\Tests\Domain\Balance\BalanceAdded;
use Storm\Tests\Domain\Balance\BalanceCreated;
use Storm\Tests\Domain\Balance\BalanceId;
use Storm\Tests\Domain\Balance\BalanceSubtracted;
use Storm\Tests\Domain\BalanceEventStore;

beforeEach(function () {
    /** @var ConnectorManager $serviceManager */
    $serviceManager = app(ConnectorManager::class);
    $manager = $serviceManager->connection('in_memory-incremental');
    $this->balanceId = BalanceId::create();

    BalanceEventStore::fromProjectionConnection($manager, new StreamName('account1'), $this->balanceId)
        ->withBalanceCreated(1, 100)
        ->withBalanceAdded(2, 10)
        ->withBalanceSubtracted(3, 5);
});

test('build a read model projector process', function () {
    /** @var ReadModelProjectorBuilder $builder */
    $builder = app(ReadModelProjectorBuilder::class);

    $readModel = new InMemoryReadModel;

    $builder
        ->connection('in_memory-incremental')
        ->initialState(fn (): array => ['events' => []])
        ->describe('create a read model on account1')
        ->name('balance')
        ->filter(new InMemoryFromToPosition)
        ->fromStreams(['account1'])
        ->withReadModel($readModel)
        ->withReactor(function (BalanceCreated $event): void {
            $this->readModel->insert($event->id(), ['total' => $event->amount()]);
        })
        ->withReactor(function (BalanceAdded $event): void {
            $this->readModel->increment($event->id(), 'total', $event->amount());
        })
        ->withReactor(function (BalanceSubtracted $event): void {
            $this->readModel->decrement($event->id(), 'total', $event->amount());
        })
        ->withThen(function (ReadModelScope $scope): void {
            $scope->userState()->push('events', $scope->event()::class);
        });

    $readModelProjector = $builder->build();
    expect($readModelProjector)->toBeInstanceOf(ReadModelProjector::class);

    $readModelProjector->run(false);
    expect($readModelProjector->getState())->toHaveKey('events', [
        BalanceCreated::class,
        BalanceAdded::class,
        BalanceSubtracted::class,
    ]);

    $container = $readModel->getContainer();
    expect($container)->toBe([
        $this->balanceId->toString() => [
            'total' => 105,
        ],
    ]);
});
