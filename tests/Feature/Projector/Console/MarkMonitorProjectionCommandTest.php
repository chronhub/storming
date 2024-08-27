<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\Console;

use Illuminate\Console\Application;
use Storm\Projector\Connector\ConnectorManager;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Repository\Data\CreateData;
use Storm\Projector\Support\Console\Monitor\MarkMonitorProjectionCommand;

beforeEach(function () {
    Application::starting(function ($artisan) {
        $artisan->resolveCommands(MarkMonitorProjectionCommand::class);
    });

    $this->provider = $this->app[ConnectorManager::class]->connection('in_memory')->projectionProvider();
    $this->provider->createProjection('balance', new CreateData('running'));
});

test('should mark a projection as resetting', function () {
    $command = $this->artisan('projector:monitor:mark', [
        'connection' => 'in_memory',
        'projection' => 'balance',
        'operation' => 'reset',
        '--no_confirmation' => true,
    ]);

    $command->expectsOutputToContain('Projection balance marked as reset')
        ->assertExitCode(0)
        ->run();

    expect($this->provider->retrieve('balance')->status())->toBe(ProjectionStatus::RESETTING->value);
});

test('should mark a projection as stopping', function () {
    $command = $this->artisan('projector:monitor:mark', [
        'connection' => 'in_memory',
        'projection' => 'balance',
        'operation' => 'stop',
        '--no_confirmation' => true,
    ]);

    $command->expectsOutputToContain('Projection balance marked as stop')
        ->assertExitCode(0)
        ->run();

    expect($this->provider->retrieve('balance')->status())->toBe(ProjectionStatus::STOPPING->value);
});

test('should mark a projection as deleting', function () {
    $command = $this->artisan('projector:monitor:mark', [
        'connection' => 'in_memory',
        'projection' => 'balance',
        'operation' => 'delete',
        '--no_confirmation' => true,
    ]);

    $command->expectsOutputToContain('Projection balance marked as delete')
        ->assertExitCode(0)
        ->run();

    expect($this->provider->retrieve('balance')->status())->toBe(ProjectionStatus::DELETING->value);
});

test('should mark a projection as deleting with emitted events', function () {
    $command = $this->artisan('projector:monitor:mark', [
        'connection' => 'in_memory',
        'projection' => 'balance',
        'operation' => 'deleteWith',
        '--no_confirmation' => true,
    ]);

    $command->expectsOutputToContain('Projection balance marked as deleteWith')
        ->assertExitCode(0)
        ->run();

    expect($this->provider->retrieve('balance')->status())->toBe(ProjectionStatus::DELETING_WITH_EMITTED_EVENTS->value);
});

test('should throw an exception if the connection is invalid', function () {
    $command = $this->artisan('projector:monitor:mark', [
        'connection' => 'invalid',
        'projection' => 'balance',
        'operation' => 'stop',
    ]);

    $command->expectsOutputToContain('No connector named invalid found.')
        ->assertExitCode(1)
        ->run();
});

test('should throw an exception if the operation is invalid', function () {
    $command = $this->artisan('projector:monitor:mark', [
        'connection' => 'in_memory',
        'projection' => 'balance',
        'operation' => 'invalid',
    ]);

    $command->expectsOutputToContain('Invalid operation invalid')
        ->assertExitCode(1)
        ->run();
});

test('should throw an exception if the projection does not exist', function () {
    $command = $this->artisan('projector:monitor:mark', [
        'connection' => 'in_memory',
        'projection' => 'invalid',
        'operation' => 'stop',
    ]);

    $command->expectsOutputToContain('Projection invalid not found')
        ->assertExitCode(1)
        ->run();
});
