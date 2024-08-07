<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\Console;

use Illuminate\Console\Application;
use Storm\Projector\ProjectorServiceManager;
use Storm\Projector\Support\Console\Edges\ProjectByMessageNameCommand;
use Storm\Stream\StreamName;
use Storm\Tests\Domain\Balance\BalanceId;
use Storm\Tests\Domain\BalanceEventStore;

use function json_decode;

test('test emit all stream events to by message name stream and partition', function () {
    $connection = 'in_memory-incremental';
    $projectionName = '$by_message_name';
    $stream1 = 'account1';
    $stream2 = 'account2';

    /** @var ProjectorServiceManager $serviceManager */
    $serviceManager = app(ProjectorServiceManager::class);

    $manager = $serviceManager->connection($connection);

    (new BalanceEventStore(
        $manager->eventStore(),
        $manager->clock(),
        new StreamName($stream1),
        BalanceId::create()
    ))->make(10);

    (new BalanceEventStore(
        $manager->eventStore(),
        $manager->clock(),
        new StreamName($stream2),
        BalanceId::create()
    ))->make(5);

    Application::starting(function ($artisan) {
        $artisan->resolveCommands(ProjectByMessageNameCommand::class);
    });

    expect($manager->projectionProvider()->exists($projectionName))->toBeFalse();

    $this->artisan('projector:edge:message-name', [
        'connection' => 'in_memory-incremental',
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

    $eventStore = $manager->eventStore();

    expect($eventStore->hasStream(new StreamName('$mn-Storm_Tests_Domain_Balance_BalanceCreated')))->toBeTrue()
        ->and($eventStore->hasStream(new StreamName('$mn-Storm_Tests_Domain_Balance_BalanceAdded')))->toBeTrue()
        ->and($eventStore->hasStream(new StreamName('$mn-Storm_Tests_Domain_Balance_BalanceSubtracted')))->toBeTrue();

    $partitions = $eventStore->filterPartitions('$mn');

    expect($partitions)->toBe([
        '$mn-Storm_Tests_Domain_Balance_BalanceCreated',
        '$mn-Storm_Tests_Domain_Balance_BalanceAdded',
        '$mn-Storm_Tests_Domain_Balance_BalanceSubtracted',
    ]);
});
