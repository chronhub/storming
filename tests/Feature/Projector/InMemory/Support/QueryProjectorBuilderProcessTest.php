<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\InMemory\Support;

use Storm\Contract\Projector\ProjectorManagement;
use Storm\Contract\Projector\QueryProjector;
use Storm\Projector\Scope\QueryProjectorScope;
use Storm\Projector\Stream\Filter\InMemoryFromToPosition;
use Storm\Projector\Support\ProcessBuilder\QueryProjectorBuilderProcess;
use Storm\Stream\StreamName;
use Storm\Tests\Domain\Balance\BalanceId;
use Storm\Tests\Domain\BalanceEventStore;

beforeEach(function () {
    /** @var ProjectorManagement $serviceManager */
    $serviceManager = app(ProjectorManagement::class);

    $manager = $serviceManager->connection('in_memory-incremental');

    (new BalanceEventStore(
        $manager->eventStore(),
        $manager->clock(),
        new StreamName('account1'),
        BalanceId::create()
    ))->make(10);
});

test('build a query projector process', function () {
    /** @var QueryProjectorBuilderProcess $builder */
    $builder = app(QueryProjectorBuilderProcess::class);

    $builder
        ->withConnection('in_memory-incremental')
        ->withInitialState(fn (): array => ['count' => 0])
        ->withDescription('query projection on account1')
        ->withProjectionName('account_balance')
        ->withQueryFilter(new InMemoryFromToPosition())
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
