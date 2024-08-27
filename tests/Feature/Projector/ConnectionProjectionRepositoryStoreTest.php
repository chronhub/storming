<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Storm\Clock\ClockFactory;
use Storm\Contract\Projector\ProjectionModel;
use Storm\Contract\Projector\ProjectionProvider;
use Storm\Projector\Checkpoint\CheckpointFactory;
use Storm\Projector\Exception\ProjectionNotFound;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Repository\DatabaseProjectionProvider;
use Storm\Projector\Repository\GenericRepository;
use Storm\Projector\Repository\LockManager;
use Storm\Projector\Repository\Projection;
use Storm\Projector\Repository\ProjectionSnapshot;
use Storm\Serializer\JsonSerializerFactory;

use function random_int;
use function usleep;

uses(RefreshDatabase::class);

beforeEach(function () {
    $connection = $this->app['db']->connection();

    $connection->getSchemaBuilder()->create(
        DatabaseProjectionProvider::TABLE_NAME, function (Blueprint $table) {
            $table->string('name')->primary();
            $table->string('status');
            $table->string('state');
            $table->string('checkpoint');
            $table->timestamp('locked_until')->nullable();
        });

    $this->clock = ClockFactory::create();
    $this->projectionProvider = new DatabaseProjectionProvider($connection, $this->clock);

    $this->lock = new LockManager($this->clock, 1000, 1000);
    $this->serializer = (new JsonSerializerFactory())->create();
    $this->repository = new GenericRepository(
        $this->projectionProvider,
        $this->lock,
        $this->serializer,
        'test'
    );
});

function createProjectionStore(GenericRepository $store, ProjectionStatus $status): void
{
    expect($store->exists())->toBeFalse();

    $store->create($status);

    expect($store->exists())->toBeTrue()
        ->and($store->loadStatus())->toBe($status);
}

function dummyProjectionSnapshot(): ProjectionSnapshot
{
    $clock = ClockFactory::create();
    $checkpoint = CheckpointFactory::from(
        'stream1',
        random_int(1, 100),
        $clock->generate(),
        $clock->generate(),
        [],
        null
    );

    return new ProjectionSnapshot([$checkpoint], ['count' => 0]);
}

function dummyProjectionSnapshot1(): ProjectionSnapshot
{
    $clock = ClockFactory::create();
    $checkpoint = CheckpointFactory::from(
        'stream1',
        random_int(100, 1000),
        $clock->generate(),
        $clock->generate(),
        [],
        null
    );

    return new ProjectionSnapshot([$checkpoint], ['count' => 25]);
}

function retrieveProjection(ProjectionProvider $provider, string $name): ?ProjectionModel
{
    return $provider->retrieve($name);
}

test('create projection', function (ProjectionStatus $status) {
    createProjectionStore($this->repository, $status);
})->with('projection status');

test('start projection', function (ProjectionStatus $status) {
    createProjectionStore($this->repository, $status);

    $this->repository->start($status);

    expect($this->repository->loadStatus())->toBe($status);
})->with('projection status');

test('stop projection', function (ProjectionStatus $status) {
    createProjectionStore($this->repository, $status);

    $this->repository->start($status);
    $this->repository->stop(dummyProjectionSnapshot(), $status);

    expect($this->repository->loadStatus())->toBe($status);
})->with('projection status');

test('release projection', function (ProjectionStatus $status) {
    createProjectionStore($this->repository, $status);

    $this->repository->start($status);
    $this->repository->release();

    expect($this->repository->loadStatus())->toBe(ProjectionStatus::IDLE);
})->with('projection status');

test('start again projection', function (ProjectionStatus $status) {
    createProjectionStore($this->repository, $status);

    $this->repository->start($status);
    $this->repository->startAgain($status);

    expect($this->repository->loadStatus())->toBe($status);
})->with('projection status');

test('persist projection', function (ProjectionStatus $status) {
    createProjectionStore($this->repository, $status);

    $this->repository->start($status);

    $lockedUntil = retrieveProjection($this->projectionProvider, $this->repository->getName())->lockedUntil();

    $snapshot = dummyProjectionSnapshot();
    $this->repository->persist($snapshot);

    $projection = retrieveProjection($this->projectionProvider, $this->repository->getName());
    expect($projection)->toBeInstanceOf(ProjectionModel::class)
        ->and($projection)->toBeInstanceOf(Projection::class)
        ->and($projection->state())->toBe($this->serializer->serialize($snapshot->userState, 'json'))
        ->and($projection->checkpoint())->toBe($this->serializer->serialize($snapshot->checkpoint, 'json'))
        ->and($projection->status())->toBe($status->value);

    // assert lock refreshed with now +lock timeout 1000 milliseconds
    $previousLock = $this->clock->from($lockedUntil)->add('milliseconds', 1000);

    expect($previousLock->isLessThan($projection->lockedUntil()))->toBeTrue();
})->with('projection status');

test('reset projection', function (ProjectionStatus $status) {
    createProjectionStore($this->repository, $status);

    $this->repository->start($status);

    $snapshot = dummyProjectionSnapshot();
    $this->repository->persist($snapshot);

    $snapshot1 = dummyProjectionSnapshot1();
    $this->repository->reset($snapshot1, $status);

    expect($this->repository->loadStatus())->toBe($status);

    $snapshot = $this->repository->loadSnapshot();
    $checkpoint = CheckpointFactory::fromArray($snapshot->checkpoint[0]);
    expect($checkpoint)->toEqual($snapshot1->checkpoint[0]);
})->with('projection status');

test('refresh lock', function () {
    $status = ProjectionStatus::RUNNING;
    createProjectionStore($this->repository, $status);

    $this->repository->start($status);
    $currentLock = $this->lock->current();
    expect($this->lock->shouldRefresh())->toBeFalse();

    // current lock + lock threshold 1000 milliseconds
    while (! $this->lock->shouldRefresh()) {
        // lock timeout
        usleep(1000);
    }

    expect($this->lock->shouldRefresh())->toBeTrue();

    $this->repository->updateLock();

    $projection = retrieveProjection($this->projectionProvider, $this->repository->getName());

    expect($projection->lockedUntil())->toBe($this->lock->current());

    // current lock should be refreshed with now +lock timeout 1000 milliseconds + lock threshold 1000 milliseconds
    $previousLock = $this->clock->from($currentLock)->add('milliseconds', 2000);

    expect($previousLock->isLessThan($projection->lockedUntil()))->toBeTrue();
});

test('delete projection', function (ProjectionStatus $status) {
    createProjectionStore($this->repository, $status);

    $this->repository->start($status);

    expect($this->repository->exists())->toBeTrue();

    // with emitted events is just a flag
    $this->repository->delete(false);

    expect($this->repository->exists())->toBeFalse();
})->with('projection status');

test('raise projection not found when load snapshot on non existing projection', function () {
    expect($this->repository->exists())->toBeFalse();

    $this->repository->loadSnapshot();
})->throws(ProjectionNotFound::class, 'Projection test not found');
