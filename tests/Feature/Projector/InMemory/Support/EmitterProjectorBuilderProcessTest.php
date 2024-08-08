<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\InMemory\Support;

use Storm\Contract\Projector\EmitterProjector;
use Storm\Contract\Projector\ProjectorManagement;
use Storm\Projector\Scope\EmitterScope;
use Storm\Projector\Stream\Filter\InMemoryFromToPosition;
use Storm\Projector\Support\Builder\EmitterProjectorBuilder;
use Storm\Stream\StreamName;
use Storm\Tests\Domain\Balance\BalanceAdded;
use Storm\Tests\Domain\Balance\BalanceCreated;
use Storm\Tests\Domain\Balance\BalanceSubtracted;
use Storm\Tests\Domain\BalanceEventStore;

beforeEach(function () {
    /** @var ProjectorManagement $serviceManager */
    $serviceManager = app(ProjectorManagement::class);

    $this->manager = $serviceManager->connection('in_memory-incremental');

    BalanceEventStore::fromProjectionConnection($this->manager, new StreamName('account1'))
        ->withBalanceCreated(1, 100)
        ->withBalanceAdded(2, 10)
        ->withBalanceSubtracted(3, 5);
});

test('build an emitter projector process', function () {
    /** @var EmitterProjectorBuilder $builder */
    $builder = app(EmitterProjectorBuilder::class);

    $builder
        ->withConnection('in_memory-incremental')
        ->withInitialState(fn (): array => ['events' => []])
        ->withDescription('emit stream event from account1 to balance event stream')
        ->withProjectionName('balance')
        ->withQueryFilter(new InMemoryFromToPosition())
        ->fromStreams(['account1'])
        ->withReactors([])
        ->withThen(function (EmitterScope $scope): void {
            $scope->userState()->push('events', $scope->event()::class);
            $scope->emit($scope->event());
        });

    expect($this->manager->eventStore()->hasStream(new StreamName('account1')))->toBeTrue()
        ->and($this->manager->eventStore()->hasStream(new StreamName('balance')))->toBeFalse();

    $emitterProjector = $builder->build();
    expect($emitterProjector)->toBeInstanceOf(EmitterProjector::class);

    $emitterProjector->run(false);

    expect($emitterProjector->getState())->toHaveKey('events', [
        BalanceCreated::class,
        BalanceAdded::class,
        BalanceSubtracted::class,
    ])
        ->and($this->manager->eventStore()->hasStream(new StreamName('account1')))->toBeTrue()
        ->and($this->manager->eventStore()->hasStream(new StreamName('balance')))->toBeTrue();
});
