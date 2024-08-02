<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Repository;

use Storm\Contract\Projector\ProjectionProvider;
use Storm\Projector\Exception\ProjectionNotFound;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Repository\Data\CreateData;
use Storm\Projector\Repository\Data\PersistData;
use Storm\Projector\Repository\Data\ReleaseData;
use Storm\Projector\Repository\Data\ResetData;
use Storm\Projector\Repository\Data\StartAgainData;
use Storm\Projector\Repository\Data\StartData;
use Storm\Projector\Repository\Data\StopData;
use Storm\Projector\Repository\Data\UpdateLockData;
use Storm\Projector\Repository\GenericRepository;
use Storm\Projector\Repository\LockManager;
use Storm\Projector\Repository\Projection;
use Storm\Serializer\JsonSerializerFactory;
use Storm\Tests\Stubs\ProjectionSnapshotStub;

beforeEach(function () {
    $this->provider = mock(ProjectionProvider::class);
    $this->lockManager = mock(LockManager::class);
    $this->serializer = (new JsonSerializerFactory())->create();
    $this->streamName = 'stream1';

    $this->projectionStore = new GenericRepository(
        $this->provider,
        $this->lockManager,
        $this->serializer,
        $this->streamName
    );

    $this->projectionSnapshotStub = new ProjectionSnapshotStub();
});

test('create projection with status', function (ProjectionStatus $status) {
    $this->provider
        ->expects('createProjection')
        ->withArgs(fn (string $streamName, CreateData $data) => $this->streamName === $streamName
            && $data->status === $status->value
        );

    $this->projectionStore->create($status);
})->with('projection status');

test('start projection with any status', function (ProjectionStatus $status) {
    $this->lockManager->expects('acquire')->andReturn('lock-1');

    $this->provider
        ->expects('acquireLock')
        ->withArgs(fn (string $streamName, StartData $data) => $this->streamName === $streamName
            && $data->status === $status->value
            && $data->lockedUntil === 'lock-1'
        );

    $this->projectionStore->start($status);
})->with('projection status');

test('stop projection with result and status', function (ProjectionStatus $status) {
    $projectionSnapshot = $this->projectionSnapshotStub->fromDefault();

    $this->lockManager->expects('refresh')->andReturn('lock-2');

    $this->provider->expects('updateProjection')
        ->withArgs(fn (string $streamName, StopData $data) => $streamName === $this->streamName
            && $data->status === $status->value
            && $data->state === $this->serializer->serialize($projectionSnapshot->userState, 'json')
            && $data->checkpoint === $this->serializer->serialize($projectionSnapshot->checkpoint, 'json')
            && $data->lockedUntil === 'lock-2');

    $this->projectionStore->stop($projectionSnapshot, $status);
})->with('projection status');

test('release projection', function () {
    $this->provider
        ->expects('updateProjection')
        ->withArgs(fn (string $streamName, ReleaseData $data) => $streamName === $this->streamName
            && $data->status === ProjectionStatus::IDLE->value
        );

    $this->projectionStore->release();
});

test('start again projection with status', function (ProjectionStatus $status) {
    $this->lockManager->shouldReceive('acquire')->andReturn('lock-3')->once();

    $this->provider
        ->expects('updateProjection')
        ->withArgs(fn (string $streamName, StartAgainData $data) => $streamName === $this->streamName
            && $data->status === $status->value
            && $data->lockedUntil === 'lock-3'
        );

    $this->projectionStore->startAgain($status);
})->with('projection status');

test('persist projection with snapshot', function () {
    $projectionSnapshot = $this->projectionSnapshotStub->fromDefault();

    $this->lockManager->shouldReceive('refresh')->andReturn('lock-4')->once();

    $this->provider
        ->expects('updateProjection')
        ->withArgs(fn (string $streamName, PersistData $data) => $streamName === $this->streamName
            && $data->state === $this->serializer->serialize($projectionSnapshot->userState, 'json')
            && $data->checkpoint === $this->serializer->serialize($projectionSnapshot->checkpoint, 'json')
            && $data->lockedUntil === 'lock-4'
        );

    $this->projectionStore->persist($projectionSnapshot);
});

test('reset projection with result and status', function (ProjectionStatus $status) {
    $projectionSnapshot = $this->projectionSnapshotStub->fromDefault();

    $this->provider
        ->expects('updateProjection')
        ->withArgs(fn (string $streamName, ResetData $data) => $streamName === $this->streamName
            && $data->status === $status->value
            && $data->state === $this->serializer->serialize($projectionSnapshot->userState, 'json')
            && $data->checkpoint === $this->serializer->serialize($projectionSnapshot->checkpoint, 'json')
        );

    $this->projectionStore->reset($projectionSnapshot, $status);
})->with('projection status');

test('delete projection', function (bool $withEmittedEvents) {
    $this->provider->expects('deleteProjection')->with($this->streamName);

    $this->projectionStore->delete($withEmittedEvents);
})->with('delete projection with emitted events');

test('load projection snapshot', function (ProjectionStatus $status) {
    $projection = new Projection(
        name: $this->streamName,
        status: $status->value,
        state: '{"user":"state"}',
        checkpoint: '{"checkpoint":"value"}',
        lockedUntil: 'lock-5'
    );

    $this->provider->expects('retrieve')->with($this->streamName)->andReturn($projection);

    $result = $this->projectionStore->loadSnapshot();

    expect($result->checkpoint)->toBe(['checkpoint' => 'value'])
        ->and($result->userState)->toBe(['user' => 'state']);
})->with('projection status');

test('raise projection not found exception with load projection snapshot', function () {
    $this->provider->expects('retrieve')->with($this->streamName)->andReturn(null);

    expect(fn () => $this->projectionStore->loadSnapshot())->toThrow(ProjectionNotFound::class);
});

test('update projection lock', function () {
    $this->lockManager->expects('shouldRefresh')->andReturn(true);
    $this->lockManager->expects('refresh')->andReturn('lock-6');

    $this->provider
        ->expects('updateProjection')
        ->withArgs(fn (string $streamName, UpdateLockData $data) => $streamName === $this->streamName
            && $data->lockedUntil === 'lock-6'
        );

    $this->projectionStore->updateLock();
});

test('does not update projection lock when should refresh return false', function () {
    $this->lockManager->expects('shouldRefresh')->andReturn(false);
    $this->lockManager->shouldNotReceive('refresh');
    $this->provider->shouldNotReceive('updateProjection');

    $this->projectionStore->updateLock();
});

test('load status of projection', function (ProjectionStatus $expectedStatus) {
    $projection = new Projection(
        name: $this->streamName,
        status: $expectedStatus->value,
        state: '{"user":"state"}',
        checkpoint: '{"checkpoint":"value"}',
        lockedUntil: 'lock-7'
    );

    $this->provider->expects('retrieve')->with($this->streamName)->andReturn($projection);

    $loadedStatus = $this->projectionStore->loadStatus();

    expect($loadedStatus)->toBe($expectedStatus);
})->with('projection status');

test('return projection running status when projection not found', function () {
    $this->provider->expects('retrieve')->with($this->streamName)->andReturn(null);

    $loadedStatus = $this->projectionStore->loadStatus();

    expect($loadedStatus)->toBe(ProjectionStatus::RUNNING);
});

test('check if projection exists', function (bool $exists) {
    $this->provider->expects('exists')->with($this->streamName)->andReturn($exists);

    $result = $this->projectionStore->exists();

    expect($result)->toBe($exists);
})->with('projection exists');

test('return current projection name', function () {
    expect($this->projectionStore->getName())->toBe($this->streamName);
});
