<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\Console;

use Storm\Projector\Checkpoint\CheckpointFactory;
use Storm\Projector\Connector\ConnectorManager;
use Storm\Projector\Repository\Data\CreateData;
use Storm\Projector\Repository\Data\PersistData;
use Storm\Projector\Repository\InMemoryProjectionProvider;

use function json_encode;

beforeEach(function () {
    /** @var InMemoryProjectionProvider $provider */
    $provider = $this->app[ConnectorManager::class]->connection('in_memory')->projectionProvider();
    $provider->createProjection('balance', new CreateData('running'));

    $this->checkpoint = CheckpointFactory::from(
        'balance',
        1,
        '2024-01-01T00:00:00.000000',
        '2024-01-01T00:00:00.000000',
        [],
        null
    );

    $provider->updateProjection('balance', new PersistData(
        json_encode(['balance' => 100]),
        json_encode([$this->checkpoint->jsonSerialize()]),
        '2024-01-01T00:00:00.000000'
    ));
});

it('should display the status of a projection', function () {
    $command = $this->artisan('projector:monitor:read', [
        'connection' => 'in_memory',
        'operation' => 'status',
        'projection' => 'balance',
    ]);

    $command
        ->expectsOutputToContain('Operation: status')
        ->expectsOutputToContain('Projection: balance')
        ->expectsOutputToContain('running')
        ->assertSuccessful()
        ->run();
});

test('should display the state of a projection', function () {
    $command = $this->artisan('projector:monitor:read', [
        'connection' => 'in_memory',
        'operation' => 'state',
        'projection' => 'balance',
    ]);

    $command
        ->expectsOutputToContain('Operation: state')
        ->expectsOutputToContain('Projection: balance')
        ->expectsTable(['Key', 'Value'], [['balance', 100]])
        ->assertSuccessful()
        ->run();
});

test('should display the checkpoint of a projection', function () {
    $command = $this->artisan('projector:monitor:read', [
        'connection' => 'in_memory',
        'operation' => 'checkpoint',
        'projection' => 'balance',
    ]);

    $command
        ->expectsOutputToContain('Operation: checkpoint')
        ->expectsOutputToContain('Projection: balance')
        ->expectsOutputToContain('Checkpoint #')
        ->expectsTable(['Key', 'Value'], [
            ['stream_name', 'balance'],
            ['position', 1],
            ['event_time', '2024-01-01T00:00:00.000000'],
            ['created_at', '2024-01-01T00:00:00.000000'],
            ['gaps', '[]'],
            ['gap_type', 'null'],
        ])
        ->assertSuccessful();

    $command->run();
});

test('should throw an exception if the connection is invalid', function () {
    $command = $this->artisan('projector:monitor:read', [
        'connection' => 'invalid',
        'operation' => 'status',
        'projection' => 'balance',
    ]);

    $command->expectsOutputToContain('No connector named invalid found.')
        ->assertFailed()
        ->run();
});

test('should throw an exception if the operation is invalid', function () {
    $command = $this->artisan('projector:monitor:read', [
        'connection' => 'in_memory',
        'operation' => 'invalid',
        'projection' => 'balance',
    ]);

    $command->expectsOutputToContain('Invalid operation [invalid] for projection [balance]')
        ->assertFailed()
        ->run();
});

test('should throw an exception if the projection does not exist', function () {
    $command = $this->artisan('projector:monitor:read', [
        'connection' => 'in_memory',
        'operation' => 'status',
        'projection' => 'invalid',
    ]);

    $command->expectsOutputToContain('Projection invalid not found')
        ->assertFailed()
        ->run();
});
