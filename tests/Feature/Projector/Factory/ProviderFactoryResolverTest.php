<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\Factory;

use Illuminate\Contracts\Foundation\Application;
use Storm\Contract\Projector\ReadModel;
use Storm\Projector\Connector\ConnectionManager;
use Storm\Projector\Connector\ConnectorManager;
use Storm\Projector\Exception\ConfigurationViolation;
use Storm\Projector\Factory\EmitterProviderFactory;
use Storm\Projector\Factory\ProviderConnectionAware;
use Storm\Projector\Factory\ProviderFactory;
use Storm\Projector\Factory\ProviderFactoryRegistry;
use Storm\Projector\Factory\ProviderFactoryResolver;
use Storm\Projector\Factory\QueryProviderFactory;
use Storm\Projector\Factory\ReadModelProviderFactory;
use Storm\Projector\Options\Option;
use Storm\Projector\Provider\Manager;

test('resolve default provider factory registered in service provider', function () {
    /** @var ProviderFactoryRegistry $registry */
    $registry = $this->app[ProviderFactoryRegistry::class];

    expect($registry)->toBeInstanceOf(ProviderFactoryResolver::class)
        ->and($registry->has('query'))->toBeTrue()
        ->and($registry->has('emitter'))->toBeTrue()
        ->and($registry->has('read_model'))->toBeTrue();

    /** @var ConnectorManager $connector */
    $connector = $this->app[ConnectorManager::class];
    $connection = $connector->connection('in_memory');

    expect($registry->resolve('query', $connection))->toBeInstanceOf(QueryProviderFactory::class)
        ->and($registry->resolve('emitter', $connection))->toBeInstanceOf(EmitterProviderFactory::class)
        ->and($registry->resolve('read_model', $connection))->toBeInstanceOf(ReadModelProviderFactory::class);
});

test('add factory from provider factory instance and resolve it', function () {
    /** @var ProviderFactoryRegistry $registry */
    $registry = $this->app[ProviderFactoryRegistry::class];

    $mockFactory = mock(ProviderFactory::class);
    $registry->register('foo', $mockFactory);

    expect($registry->resolve('foo', mock(ConnectionManager::class)))->toBe($mockFactory);
});

test('add binding factory and resolve it', function () {
    /** @var ProviderFactoryRegistry $registry */
    $registry = $this->app[ProviderFactoryRegistry::class];

    $factory = new readonly class implements ProviderConnectionAware, ProviderFactory
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
    /** @var ProviderFactoryRegistry $registry */
    $registry = $this->app[ProviderFactoryRegistry::class];

    $mockConnection = mock(ConnectionManager::class);
    $mockFactory = mock(ProviderFactory::class);

    $closureFactory = function (ConnectionManager $connection, Application $app) use ($mockConnection, $mockFactory) {
        expect($connection)->toBe($mockConnection);

        return $mockFactory;
    };

    $registry->register('query', $closureFactory);
    $resolvedFactory = $registry->resolve('query', $mockConnection);

    expect($resolvedFactory)->toBe($mockFactory);
});

test('add Closure factory and resolve it', function () {
    /** @var ProviderFactoryRegistry $registry */
    $registry = $this->app[ProviderFactoryRegistry::class];

    $mockConnection = mock(ConnectionManager::class);
    $mockFactory = mock(ProviderFactory::class);

    $closureFactory = function (ConnectionManager $connection, Application $app) use ($mockConnection, $mockFactory) {
        expect($connection)->toBe($mockConnection);

        return $mockFactory;
    };

    $registry->register('foo', $closureFactory);

    $resolvedFactory = $registry->resolve('foo', $mockConnection);

    expect($resolvedFactory)->toBe($mockFactory);
});

test('raise exception when factory not found', function () {
    /** @var ProviderFactoryRegistry $registry */
    $registry = $this->app[ProviderFactoryRegistry::class];
    $registry->resolve('foo', mock(ConnectionManager::class));
})->throws(ConfigurationViolation::class, 'Provider factory foo not found');
