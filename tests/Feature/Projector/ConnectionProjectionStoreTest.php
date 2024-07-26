<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Storm\Clock\ClockFactory;
use Storm\Projector\Checkpoint\CheckpointFactory;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Repository\ConnectionProjectionProvider;
use Storm\Projector\Repository\LockManager;
use Storm\Projector\Repository\ProjectionRepositoryStore;
use Storm\Projector\Repository\ProjectionSnapshot;
use Storm\Serializer\JsonSerializerFactory;

uses(RefreshDatabase::class);

beforeEach(function () {
    $connection = $this->app['db']->connection();

    $connection->getSchemaBuilder()->create(
        ConnectionProjectionProvider::TABLE_NAME, function (Blueprint $table) {
            $table->string('name')->primary();
            $table->string('status');
            $table->string('state');
            $table->string('checkpoint');
            $table->timestamp('locked_until')->nullable();
        });

    $this->projectionName = 'test';
    $this->serializer = (new JsonSerializerFactory)->create();
    $this->clock = ClockFactory::create();
    $this->projectionProvider = new ConnectionProjectionProvider(
        $connection,
        $this->clock
    );
});

test('load snapshot', function () {
    $lockManager = new LockManager($this->clock, 1000, 1000);
    $store = new ProjectionRepositoryStore(
        $this->projectionProvider,
        $lockManager,
        $this->serializer,
        $this->projectionName,
    );

    $store->create(ProjectionStatus::RUNNING);
    $store->start(ProjectionStatus::RUNNING);

    $checkpoints = [
        CheckpointFactory::from(
            'stream1',
            10,
            $this->clock->generate(),
            $this->clock->generate(),
            [],
            null,
        ),
        CheckpointFactory::from(
            'stream2',
            61,
            $this->clock->generate(),
            $this->clock->generate(),
            [10, 20, 60],
            null,
        ),
    ];

    $userState = ['count' => 25];

    $snapshot = new ProjectionSnapshot($checkpoints, $userState);

    $store->persist($snapshot);

    $snapshotLoaded = $store->loadSnapshot();

    expect(CheckpointFactory::fromArray($snapshotLoaded->checkpoints[0]))->toEqual($checkpoints[0])
        ->and(CheckpointFactory::fromArray($snapshotLoaded->checkpoints[1]))->toEqual($checkpoints[1]);
});

test('incomplete test', function () {})->todo();
