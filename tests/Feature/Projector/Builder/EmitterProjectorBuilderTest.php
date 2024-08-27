<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\Builder;

use Storm\Contract\Projector\EmitterProjector;
use Storm\Projector\Connector\ConnectorManager;
use Storm\Projector\Scope\EmitterScope;
use Storm\Projector\Support\Builder\EmitterProjectorBuilder;
use Storm\Stream\StreamName;
use Storm\Tests\Domain\BalanceEventStore;

test('test emitter builder from partition', function () {
    $connection = 'in_memory-incremental';
    $projectionName = 'account';
    $stream1 = 'account-one';
    $stream2 = 'account-two';

    /** @var ConnectorManager $serviceManager */
    $serviceManager = app(ConnectorManager::class);
    $manager = $serviceManager->connection($connection);

    BalanceEventStore::fromProjectionConnection($manager, new StreamName($stream1))->make(10);
    BalanceEventStore::fromProjectionConnection($manager, new StreamName($stream2))->make(5);

    expect($manager->projectionProvider()->exists($projectionName))->toBeFalse()
        ->and($manager->eventStore()->hasStream(new StreamName($stream1)))->toBeTrue()
        ->and($manager->eventStore()->hasStream(new StreamName($stream2)))->toBeTrue()
        ->and($manager->eventStore()->hasStream(new StreamName($projectionName)))->toBeFalse()
        ->and($manager->eventStore()->filterPartitions('account'))->toBe(['account-one', 'account-two']);

    /** @var EmitterProjectorBuilder $emitterBuilder */
    $emitterBuilder = $this->app[EmitterProjectorBuilder::class];

    $builder = $emitterBuilder
        ->connection($connection)
        ->name($projectionName)
        ->fromPartitions(['account'])
        ->then(function (EmitterScope $scope): void {
            $scope->emit($scope->event());
        })->build();

    expect($builder)->toBeInstanceOf(EmitterProjector::class);

    $builder->run(false);

    expect($manager->eventStore()->hasStream(new StreamName($projectionName)))->toBeTrue();
});

test('test emitter builder from all streams and filter internal streams starting with a dollar sign', function () {
    $connection = 'in_memory-incremental';
    $projectionName = 'account';
    $stream1 = 'account_one';
    $stream2 = '$account_two';

    /** @var ConnectorManager $serviceManager */
    $serviceManager = app(ConnectorManager::class);
    $manager = $serviceManager->connection($connection);

    BalanceEventStore::fromProjectionConnection($manager, new StreamName($stream1))->make(10);
    BalanceEventStore::fromProjectionConnection($manager, new StreamName($stream2))->make(5);

    expect($manager->projectionProvider()->exists($projectionName))->toBeFalse()
        ->and($manager->eventStore()->hasStream(new StreamName($stream1)))->toBeTrue()
        ->and($manager->eventStore()->hasStream(new StreamName($stream2)))->toBeTrue()
        ->and($manager->eventStore()->hasStream(new StreamName($projectionName)))->toBeFalse()
        ->and($manager->eventStore()->filterStreams($stream1, $stream2))->toBe(['account_one', '$account_two']);

    /** @var EmitterProjectorBuilder $emitterBuilder */
    $emitterBuilder = $this->app[EmitterProjectorBuilder::class];

    $builder = $emitterBuilder
        ->connection($connection)
        ->name($projectionName)
        ->fromAll()
        ->then(function (EmitterScope $scope): void {
            expect($scope->streamName())->toBe('account_one');
            $scope->emit($scope->event());
        })->build();

    expect($builder)->toBeInstanceOf(EmitterProjector::class);

    $builder->run(false);

    expect($manager->eventStore()->hasStream(new StreamName($projectionName)))->toBeTrue();
});

test('test emitter builder from streams', function () {
    $connection = 'in_memory-incremental';
    $projectionName = 'account';
    $stream1 = 'account_one';
    $stream2 = 'account-two';

    /** @var ConnectorManager $serviceManager */
    $serviceManager = app(ConnectorManager::class);
    $manager = $serviceManager->connection($connection);

    BalanceEventStore::fromProjectionConnection($manager, new StreamName($stream1))->make(10);
    BalanceEventStore::fromProjectionConnection($manager, new StreamName($stream2))->make(5);

    expect($manager->projectionProvider()->exists($projectionName))->toBeFalse()
        ->and($manager->eventStore()->hasStream(new StreamName($stream1)))->toBeTrue()
        ->and($manager->eventStore()->hasStream(new StreamName($stream2)))->toBeTrue()
        ->and($manager->eventStore()->hasStream(new StreamName($projectionName)))->toBeFalse()
        ->and($manager->eventStore()->filterStreams($stream1, $stream2))->toBe(['account_one']);

    /** @var EmitterProjectorBuilder $emitterBuilder */
    $emitterBuilder = $this->app[EmitterProjectorBuilder::class];

    $builder = $emitterBuilder
        ->connection($connection)
        ->name($projectionName)
        ->fromStreams(['account_one', 'account-two'])
        ->then(function (EmitterScope $scope): void {
            expect($scope->streamName())->toBeIn(['account_one', 'account-two']);
            $scope->emit($scope->event());
        })->build();

    expect($builder)->toBeInstanceOf(EmitterProjector::class);

    $builder->run(false);

    expect($manager->eventStore()->hasStream(new StreamName($projectionName)))->toBeTrue();
});
