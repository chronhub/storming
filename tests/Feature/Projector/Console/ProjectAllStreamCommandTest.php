<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\Console;

use Illuminate\Console\Application;
use Storm\Contract\Projector\ProjectorManagement;
use Storm\Projector\Support\Console\Edges\ProjectAllStreamCommand;
use Storm\Stream\StreamName;
use Storm\Tests\Domain\Balance\BalanceId;
use Storm\Tests\Domain\BalanceEventStore;

use function json_decode;

test('test emit all stream events to internal stream all', function () {
    $connection = 'in_memory-incremental';
    $projectionName = '$all';
    $stream1 = 'account1';
    $stream2 = 'account2';

    /** @var ProjectorManagement $serviceManager */
    $serviceManager = app(ProjectorManagement::class);
    $manager = $serviceManager->connection($connection);

    BalanceEventStore::fromProjectionConnection(
        $manager,
        new StreamName($stream1),
        BalanceId::create()
    )->make(10);

    BalanceEventStore::fromProjectionConnection(
        $manager,
        new StreamName($stream2),
        BalanceId::create()
    )->make(5);

    Application::starting(function ($artisan) {
        $artisan->resolveCommands(ProjectAllStreamCommand::class);
    });

    expect($manager->projectionProvider()->exists($projectionName))->toBeFalse();

    $this->artisan('projector:edge:all', [
        'connection' => 'in_memory-incremental',
        'build' => 'projection.emitter.edge-all',
        '--signal' => false,
        '--in-background' => false,
    ])->run();

    expect($manager->projectionProvider()->exists($projectionName))->toBeTrue();

    $model = $manager->projectionProvider()->retrieve($projectionName);
    $checkpoint = json_decode($model->checkpoint(), true);

    expect($checkpoint[$stream1])->toBeArray()
        ->toHaveKey('stream_name', $stream1)
        ->toHaveKey('position', 10)
        ->and($checkpoint[$stream2])->toBeArray()
        ->toHaveKey('stream_name', $stream2)
        ->toHaveKey('position', 5);
});
