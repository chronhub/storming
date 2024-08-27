<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\InMemory\Support;

use Storm\Contract\Projector\QueryProjector;
use Storm\Projector\Connector\ConnectorManager;
use Storm\Projector\Scope\QueryProjectorScope;
use Storm\Projector\Stream\Filter\InMemoryFromToPosition;
use Storm\Projector\Support\Builder\QueryProjectorBuilder;
use Storm\Stream\StreamName;
use Storm\Tests\Domain\BalanceEventStore;

beforeEach(function () {
    /** @var ConnectorManager $serviceManager */
    $serviceManager = app(ConnectorManager::class);
    $manager = $serviceManager->connection('in_memory-incremental');

    BalanceEventStore::fromProjectionConnection($manager, new StreamName('account1'))->make(10);
});

test('build a query projector process', function () {
    /** @var QueryProjectorBuilder $builder */
    $builder = app(QueryProjectorBuilder::class);

    $builder
        ->connection('in_memory-incremental')
        ->initialState(fn (): array => ['count' => 0])
        ->describe('query projection on account1')
        ->name('account_balance')
        ->filter(new InMemoryFromToPosition)
        ->fromStreams(['account1'])
        ->withReactors([])
        ->withThen(function (QueryProjectorScope $scope): void {
            $scope->userState()->increment('count');
        })
        ->withOptions([]);

    $queryProjector = $builder->build();

    expect($queryProjector)->toBeInstanceOf(QueryProjector::class);

    $queryProjector->run(false);

    expect($queryProjector->getState())->toHaveKey('count', 10);
});
