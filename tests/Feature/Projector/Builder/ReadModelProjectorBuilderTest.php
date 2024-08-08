<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\Builder;

use Storm\Contract\Projector\ProjectorManagement;
use Storm\Contract\Projector\ReadModelProjector;
use Storm\Projector\Scope\ReadModelScope;
use Storm\Projector\Support\Builder\ReadModelProjectorBuilder;
use Storm\Projector\Support\ReadModel\InMemoryReadModel;
use Storm\Stream\StreamName;
use Storm\Tests\Domain\Balance\BalanceAdded;
use Storm\Tests\Domain\Balance\BalanceCreated;
use Storm\Tests\Domain\Balance\BalanceId;
use Storm\Tests\Domain\Balance\BalanceSubtracted;
use Storm\Tests\Domain\BalanceEventStore;

beforeEach(function () {
    $this->readModel = new InMemoryReadModel();
});

test('test read model builder from partition', function () {
    $connection = 'in_memory-incremental';
    $projectionName = 'balances';
    $stream1 = 'account-balance';

    /** @var ProjectorManagement $serviceManager */
    $serviceManager = app(ProjectorManagement::class);
    $manager = $serviceManager->connection($connection);

    $balanceId = BalanceId::create();
    BalanceEventStore::fromProjectionConnection($manager, new StreamName($stream1), $balanceId)
        ->make(10, withNoOp: true);

    expect($manager->projectionProvider()->exists($projectionName))->toBeFalse()
        ->and($manager->eventStore()->hasStream(new StreamName($stream1)))->toBeTrue()
        ->and($manager->eventStore()->hasStream(new StreamName($projectionName)))->toBeFalse();

    /** @var ReadModelProjectorBuilder $readModelBuilder */
    $readModelBuilder = $this->app[ReadModelProjectorBuilder::class];

    $builder = $readModelBuilder
        ->withConnection($connection)
        ->withInitialState(fn () => ['balances' => 0])
        ->withProjectionName($projectionName)
        ->withReadModel($this->readModel)
        ->fromPartitions(['account'])
        ->withThen(function (ReadModelScope $scope): void {
            $event = $scope->event();

            if ($event instanceof BalanceCreated) {
                $scope->userState()->increment('balances');
                $scope->stack('insert', $event->id(), ['total' => $event->amount()]);
            }

            if ($event instanceof BalanceAdded) {
                $scope->userState()->increment('balances');
                $scope->stack('increment', $event->id(), 'total', $event->amount());
            }

            if ($event instanceof BalanceSubtracted) {
                $scope->userState()->decrement('balances');
                $scope->stack('decrement', $event->id(), 'total', $event->amount());
            }
        })->build();

    expect($builder)->toBeInstanceOf(ReadModelProjector::class);
    $builder->run(false);

    expect($manager->eventStore()->hasStream(new StreamName($projectionName)))->toBeFalse();

    $container = $this->readModel->getContainer();
    expect($container)->toHaveKey($balanceId->toString())
        ->and($container[$balanceId->toString()])->toHaveKey('total');
});
