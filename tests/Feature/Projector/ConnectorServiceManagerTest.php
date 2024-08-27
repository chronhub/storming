<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector;

use Illuminate\Contracts\Foundation\Application;
use Storm\Projector\Connector\ConnectorManager;
use Storm\Projector\Connector\InMemoryConnectionManager;
use Storm\Projector\Connector\InMemoryConnector;
use Storm\Projector\Connector\ManageConnector;
use Storm\Projector\Exception\ConfigurationViolation;

beforeEach(function () {
    $this->manager = $this->app->make(ConnectorManager::class);
});

test('registered with connectors', function () {
    $defaultDriver = $this->app['config']['projector.default'];

    expect($this->manager)->toBeInstanceOf(ManageConnector::class)
        ->and($this->manager->getDefaultDriver())->toBe($defaultDriver)
        ->and($this->manager->connected($defaultDriver))->toBeFalse()
        ->and($this->manager->connected('in_memory-incremental'))->toBeFalse();

    $connection = $this->manager->connection();
    expect($connection)->toBeInstanceOf(InMemoryConnectionManager::class)
        ->and($this->manager->connected($defaultDriver))->toBeTrue();

    $sameConnection = $this->manager->connection($defaultDriver);
    expect($sameConnection)->toBe($connection);

    $otherConnection = $this->manager->connection('in_memory-incremental');
    expect($otherConnection)
        ->toBeInstanceOf(InMemoryConnectionManager::class)
        ->and($otherConnection)->not->toBe($connection)
        ->and($this->manager->connected('in_memory-incremental'))->toBeTrue();
});

test('set default driver', function () {
    $this->manager->setDefaultDriver('in_memory-incremental');
    expect($this->manager->getDefaultDriver())->toBe('in_memory-incremental')
        ->and(config('projector.default'))->toBe('in_memory-incremental');

    $connection = $this->manager->connection();
    expect($connection)->toBeInstanceOf(InMemoryConnectionManager::class);

    $sameConnectionManager = $this->manager->connection('in_memory-incremental');
    expect($sameConnectionManager)->toBe($connection);
});

test('add connector', function () {
    $copyConfig = $this->app['config']->get('projector.connection.in_memory');
    $this->app['config']->set('projector.connection.foo', $copyConfig);

    $this->manager->addConnector('foo', fn (Application $app) => new InMemoryConnector($app));

    $connection = $this->manager->connection('foo');
    expect($connection)->toBeInstanceOf(InMemoryConnectionManager::class);
});

test('raise exception when adding duplicate connector which is not resolved', function () {
    $this->manager->addConnector('in_memory', fn (Application $app) => new InMemoryConnector($app));
})->throws(ConfigurationViolation::class, 'Connector in_memory already exists.');

test('raise exception when adding duplicate connector which is resolved', function () {
    $this->manager->connection('in_memory');
    $this->manager->addConnector('in_memory', fn (Application $app) => new InMemoryConnector($app));
})->throws(ConfigurationViolation::class, 'Connector in_memory already exists.');

test('raise exception when no connector found', function () {
    $this->manager->connection('unknown');
})->throws(ConfigurationViolation::class, 'No connector named unknown found.');

test('raise exception when configuration of connector is not an array or empty', function (mixed $config) {
    config()->set('projector.connection.in_memory-incremental', $config);
    $this->manager->connection('in_memory-incremental');
})
    ->with([['foo'], [null], [[]]])
    ->throws(ConfigurationViolation::class, 'No configuration found for connector in_memory-incremental.');
