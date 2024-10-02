<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\Factory;

use Illuminate\Contracts\Foundation\Application;
use Storm\Contract\Projector\ReadModel;
use Storm\Projector\Connector\ConnectionManager;
use Storm\Projector\Connector\ConnectorManager;
use Storm\Projector\Exception\ConfigurationViolation;
use Storm\Projector\Factory\EmitterFactory;
use Storm\Projector\Factory\Factory;
use Storm\Projector\Factory\ProviderConnectionAware;
use Storm\Projector\Factory\ProviderResolver;
use Storm\Projector\Factory\QueryFactory;
use Storm\Projector\Factory\ReadModelFactory;
use Storm\Projector\Factory\Registry;
use Storm\Projector\Options\Option;
use Storm\Projector\Projection\Manager;

test('resolve default provider factory registered in service provider', function () {
    /** @var Registry $registry */
    $registry = $this->app[Registry::class];

    expect($registry)->toBeInstanceOf(ProviderResolver::class)
        ->and($registry->has('query'))->toBeTrue()
        ->and($registry->has('emitter'))->toBeTrue()
        ->and($registry->has('read_model'))->toBeTrue();

    /** @var ConnectorManager $connector */
    $connector = $this->app[ConnectorManager::class];
    $connection = $connector->connection('in_memory');

    expect($registry->resolve('query', $connection))->toBeInstanceOf(QueryFactory::class)
        ->and($registry->resolve('emitter', $connection))->toBeInstanceOf(EmitterFactory::class)
        ->and($registry->resolve('read_model', $connection))->toBeInstanceOf(ReadModelFactory::class);
});

test('add factory from provider factory instance and resolve it', function () {
    /** @var Registry $registry */
    $registry = $this->app[Registry::class];

    $mockFactory = mock(Factory::class);
    $registry->register('foo', $mockFactory);

    expect($registry->resolve('foo', mock(ConnectionManager::class)))->toBe($mockFactory);
});

test('add binding factory and resolve it', function () {
    /** @var Registry $registry */
    $registry = $this->app[Registry::class];

    $factory = new readonly class implements Factory, ProviderConnectionAware
    {
        /** @phpstan-ignore-next-line */
        public ?ConnectionManager $connection;

        public function create(?string $streamName, ?ReadModel $readModel, Option $options): Manager
        {
            return mock(Manager::class);
        }

        public function setConnection(ConnectionManager $connection): void
        {
            /**@phpstan-ignore-next-line */
            $this->connection = $connection;
        }
    };

    $abstract = $factory::class;

    $this->app->instance($abstract, $factory);
    $registry->register('foo', $abstract);

    $mockConnection = mock(ConnectionManager::class);
    $resolvedFactory = $registry->resolve('foo', $mockConnection);

    expect($resolvedFactory)->toBeInstanceOf($abstract)
        /** @phpstan-ignore-next-line */
        ->and($resolvedFactory->connection)->toBe($mockConnection);
});

test('override query with Closure factory and resolve it', function () {
    /** @var Registry $registry */
    $registry = $this->app[Registry::class];

    $mockConnection = mock(ConnectionManager::class);
    $mockFactory = mock(Factory::class);

    $closureFactory = function (ConnectionManager $connection, Application $app) use ($mockConnection, $mockFactory) {
        expect($connection)->toBe($mockConnection);

        return $mockFactory;
    };

    $registry->register('query', $closureFactory);
    $resolvedFactory = $registry->resolve('query', $mockConnection);

    expect($resolvedFactory)->toBe($mockFactory);
});

test('add Closure factory and resolve it', function () {
    /** @var Registry $registry */
    $registry = $this->app[Registry::class];

    $mockConnection = mock(ConnectionManager::class);
    $mockFactory = mock(Factory::class);

    $closureFactory = function (ConnectionManager $connection, Application $app) use ($mockConnection, $mockFactory) {
        expect($connection)->toBe($mockConnection);

        return $mockFactory;
    };

    $registry->register('foo', $closureFactory);

    $resolvedFactory = $registry->resolve('foo', $mockConnection);

    expect($resolvedFactory)->toBe($mockFactory);
});

test('raise exception when factory not found', function () {
    /** @var Registry $registry */
    $registry = $this->app[Registry::class];
    $registry->resolve('foo', mock(ConnectionManager::class));
})->throws(ConfigurationViolation::class, 'Provider factory foo not found');
