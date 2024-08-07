<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\Console;

use Illuminate\Console\Application;
use Storm\Projector\Checkpoint\CheckpointFactory;
use Storm\Projector\Repository\Data\CreateData;
use Storm\Projector\Repository\Data\PersistData;
use Storm\Projector\Repository\InMemoryProjectionProvider;
use Storm\Projector\Support\Console\ReadMonitorProjectionCommand;

use function json_encode;

beforeEach(function () {
    Application::starting(function ($artisan) {
        $artisan->resolveCommands(ReadMonitorProjectionCommand::class);
    });

    /** @var InMemoryProjectionProvider $provider */
    $provider = $this->app['projector.provider.in_memory'];
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
        json_encode(['balance' => 100], JSON_PRETTY_PRINT),
        json_encode($this->checkpoint->jsonSerialize(), JSON_PRETTY_PRINT),
        '2024-01-01T00:00:00.000000'
    ));
});

it('should display the status of a projection', function () {
    $command = $this->artisan('projector:monitor:read', [
        'connection' => 'in_memory',
        'operation' => 'status',
        'projection' => 'balance',
    ]);

    $command->expectsOutput('Status of projection balance: running')
        ->assertExitCode(0)
        ->run();
});

test('should display the state of a projection', function () {
    $command = $this->artisan('projector:monitor:read', [
        'connection' => 'in_memory',
        'operation' => 'state',
        'projection' => 'balance',
    ]);

    $command
        ->expectsOutput('State of projection balance: {"balance":100}')
        ->assertExitCode(0)
        ->run();
});

test('should display the checkpoint of a projection', function () {
    $command = $this->artisan('projector:monitor:read', [
        'connection' => 'in_memory',
        'operation' => 'checkpoint',
        'projection' => 'balance',
    ]);

    Application::starting(function ($artisan) {
        $artisan->resolveCommands(ReadMonitorProjectionCommand::class);
    });

    $command
        ->expectsOutput('Checkpoint of projection balance: '.json_encode($this->checkpoint->jsonSerialize()))
        ->assertExitCode(0)
        ->run();
});

test('should throw an exception if the connection is invalid', function () {
    $command = $this->artisan('projector:monitor:read', [
        'connection' => 'invalid',
        'operation' => 'status',
        'projection' => 'balance',
    ]);

    $command->expectsOutputToContain('No connector named invalid found.')
        ->assertExitCode(1)
        ->run();
});

test('should throw an exception if the operation is invalid', function () {
    $command = $this->artisan('projector:monitor:read', [
        'connection' => 'in_memory',
        'operation' => 'invalid',
        'projection' => 'balance',
    ]);

    $command->expectsOutputToContain('Invalid operation invalid for balance')
        ->assertExitCode(1)
        ->run();
});

test('should throw an exception if the projection does not exist', function () {
    $command = $this->artisan('projector:monitor:read', [
        'connection' => 'in_memory',
        'operation' => 'status',
        'projection' => 'invalid',
    ]);

    $command->expectsOutputToContain('Projection invalid not found')
        ->assertExitCode(1)
        ->run();
});
