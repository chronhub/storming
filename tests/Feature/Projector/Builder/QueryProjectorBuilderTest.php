<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\Builder;

use Storm\Contract\Projector\QueryProjector;
use Storm\Projector\Connector\ConnectorManager;
use Storm\Projector\Scope\QueryProjectorScope;
use Storm\Projector\Support\Builder\QueryProjectorBuilder;
use Storm\Stream\StreamName;
use Storm\Tests\Domain\Balance\BalanceAdded;
use Storm\Tests\Domain\Balance\BalanceCreated;
use Storm\Tests\Domain\Balance\BalanceId;
use Storm\Tests\Domain\Balance\BalanceNoOp;
use Storm\Tests\Domain\Balance\BalanceSubtracted;
use Storm\Tests\Domain\BalanceEventStore;

test('test query builder from partition', function () {
    $connection = 'in_memory-incremental';
    $projectionName = 'balances';
    $stream1 = 'account-balance';

    /** @var ConnectorManager $serviceManager */
    $serviceManager = app(ConnectorManager::class);
    $manager = $serviceManager->connection($connection);

    $balanceId = BalanceId::create();
    BalanceEventStore::fromProjectionConnection($manager, new StreamName($stream1), $balanceId)
        ->withBalanceCreated(1, 250)
        ->withBalanceAdded(2, 50)
        ->withBalanceNoOp(3)
        ->withBalanceSubtracted(4, -250)
        ->withBalanceNoOp(5)
        ->withBalanceNoOp(6)
        ->withBalanceNoOp(7);

    expect($manager->projectionProvider()->exists($projectionName))->toBeFalse()
        ->and($manager->eventStore()->hasStream(new StreamName($stream1)))->toBeTrue()
        ->and($manager->eventStore()->hasStream(new StreamName($projectionName)))->toBeFalse();

    /** @var QueryProjectorBuilder $queryProjectorBuilder */
    $queryProjectorBuilder = $this->app[QueryProjectorBuilder::class];

    $queryProjector = $queryProjectorBuilder
        ->connection($connection)
        ->initialState(fn () => ['balances' => 0, 'no_op' => []])
        ->name($projectionName)
        ->fromPartitions(['account'])
        ->then(function (QueryProjectorScope $scope): void {
            $event = $scope->event();

            if ($event instanceof BalanceCreated) {
                $scope->userState()->increment('balances', $event->amount());
            }

            if ($event instanceof BalanceAdded) {
                $scope->userState()->increment('balances', $event->amount());
            }

            if ($event instanceof BalanceSubtracted) {
                $scope->userState()->decrement('balances', $event->amount());
            }

            if ($event instanceof BalanceNoOp) {
                $scope->userState()->push('no_op', $event::class);
            }
        })->build();

    expect($queryProjector)->toBeInstanceOf(QueryProjector::class);

    $queryProjector->run(false);

    expect($queryProjector->getState())->toBeArray()
        ->and($queryProjector->getState()['balances'])->toBe(50)
        ->and($queryProjector->getState()['no_op'])->toBe([
            BalanceNoOp::class,
            BalanceNoOp::class,
            BalanceNoOp::class,
            BalanceNoOp::class,
        ]);
});
