<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Repository;

use Storm\Contract\Projector\ProjectionProvider;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Repository\Data\CreateData;
use Storm\Projector\Repository\Data\PersistData;
use Storm\Projector\Repository\Data\ReleaseData;
use Storm\Projector\Repository\Data\ResetData;
use Storm\Projector\Repository\Data\StartAgainData;
use Storm\Projector\Repository\Data\StartData;
use Storm\Projector\Repository\Data\StopData;
use Storm\Projector\Repository\Data\UpdateLockData;
use Storm\Projector\Repository\LockManager;
use Storm\Projector\Repository\Projection;
use Storm\Projector\Repository\ProjectionResult;
use Storm\Projector\Repository\ProjectionStore;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

beforeEach(function () {
    $this->provider = mock(ProjectionProvider::class);
    $this->lockManager = mock(LockManager::class);
    $this->serializer = new Serializer([], [new JsonEncoder()]); // fixme: json serializer
    $this->streamName = 'stream-1';

    $this->projectionStore = new ProjectionStore(
        $this->provider,
        $this->lockManager,
        $this->serializer,
        $this->streamName
    );
});

it('create projection with status', function (ProjectionStatus $status) {
    $this->provider
        ->shouldReceive('createProjection')
        ->withArgs(fn (string $streamName, CreateData $data) => $this->streamName === $streamName && $data->status === $status->value)
        ->once();

    $this->projectionStore->create($status);
})->with([ProjectionStatus::cases()]);

it('start projection with any status', function (ProjectionStatus $status) {
    $this->lockManager->shouldReceive('acquire')->andReturn('lock-1')->once();

    $this->provider
        ->shouldReceive('acquireLock')
        ->withArgs(fn (string $streamName, StartData $data) => $this->streamName === $streamName && $data->status === $status->value && $data->lockedUntil === 'lock-1')
        ->once();

    $this->projectionStore->start($status);
})->with([ProjectionStatus::cases()]);

it('stop projection with result and status', function (ProjectionStatus $status) {
    $projectionResult = new ProjectionResult(
        checkpoints: ['checkpoint' => 'value'],
        userState: ['user' => 'state']
    );

    $this->lockManager->shouldReceive('refresh')->andReturn('lock-2')->once();

    $this->provider->shouldReceive('updateProjection')
        ->withArgs(function (string $streamName, StopData $data) use ($status) {
            return $streamName === $this->streamName
                && $data->status === $status->value
                && $data->state === '{"user":"state"}'
                && $data->checkpoint === '{"checkpoint":"value"}'
                && $data->lockedUntil === 'lock-2';
        })->once();

    $this->projectionStore->stop($projectionResult, $status);
})->with([ProjectionStatus::cases()]);

it('release projection', function () {
    $this->provider
        ->shouldReceive('updateProjection')
        ->withArgs(fn (string $streamName, ReleaseData $data) => $streamName === $this->streamName && $data->status === ProjectionStatus::IDLE->value)
        ->once();

    $this->projectionStore->release();
});

it('start again projection with status', function (ProjectionStatus $status) {
    $this->lockManager->shouldReceive('acquire')->andReturn('lock-3')->once();

    $this->provider
        ->shouldReceive('updateProjection')
        ->withArgs(fn (string $streamName, StartAgainData $data) => $streamName === $this->streamName && $data->status === $status->value && $data->lockedUntil === 'lock-3')
        ->once();

    $this->projectionStore->startAgain($status);
})->with([ProjectionStatus::cases()]);

it('persist projection with result', function () {
    $projectionResult = new ProjectionResult(
        checkpoints: ['checkpoint' => 'value'],
        userState: ['user' => 'state']
    );

    $this->lockManager->shouldReceive('refresh')->andReturn('lock-4')->once();

    $this->provider->shouldReceive('updateProjection')
        ->withArgs(function (string $streamName, PersistData $data) {
            return $streamName === $this->streamName
                && $data->state === '{"user":"state"}'
                && $data->checkpoint === '{"checkpoint":"value"}'
                && $data->lockedUntil === 'lock-4';
        })->once();

    $this->projectionStore->persist($projectionResult);
});

it('reset projection with result and status', function (ProjectionStatus $status) {
    $projectionResult = new ProjectionResult(
        checkpoints: ['checkpoint' => 'value'],
        userState: ['user' => 'state']
    );

    $this->provider->shouldReceive('updateProjection')
        ->withArgs(function (string $streamName, ResetData $data) use ($status) {
            return $streamName === $this->streamName
                && $data->status === $status->value
                && $data->state === '{"user":"state"}'
                && $data->checkpoint === '{"checkpoint":"value"}';
        })->once();

    $this->projectionStore->reset($projectionResult, $status);
})->with([ProjectionStatus::cases()]);

it('delete projection', function (bool $withEmittedEvents) {
    $this->provider
        ->shouldReceive('deleteProjection')
        ->with($this->streamName)
        ->once();

    $this->projectionStore->delete($withEmittedEvents);
})->with(['emitted events' => [true, false]]);

it('load detail of projection', function (ProjectionStatus $status) {
    $projection = new Projection(
        name: $this->streamName,
        status: $status->value,
        state: '{"user":"state"}',
        checkpoint: '{"checkpoint":"value"}',
        lockedUntil: 'lock-5'
    );

    $this->provider->shouldReceive('retrieve')
        ->with($this->streamName)
        ->andReturn($projection)
        ->once();

    $result = $this->projectionStore->loadDetail();

    expect($result->checkpoints)->toBe(['checkpoint' => 'value'])
        ->and($result->userState)->toBe(['user' => 'state']);
})->with([ProjectionStatus::cases()]);

it('update projection lock', function () {
    $this->lockManager->shouldReceive('shouldRefresh')->andReturn(true)->once();
    $this->lockManager->shouldReceive('refresh')->andReturn('lock-6')->once();

    $this->provider
        ->shouldReceive('updateProjection')
        ->withArgs(fn (string $streamName, UpdateLockData $data) => $streamName === $this->streamName && $data->lockedUntil === 'lock-6')
        ->once();

    $this->projectionStore->updateLock();
});

it('does not update projection lock when should refresh return false', function () {
    $this->lockManager->shouldReceive('shouldRefresh')->andReturn(false)->once();
    $this->lockManager->shouldNotReceive('refresh');
    $this->provider->shouldNotReceive('updateProjection');

    $this->projectionStore->updateLock();
});

it('load status of projection', function (ProjectionStatus $expectedStatus) {
    $projection = new Projection(
        name: $this->streamName,
        status: $expectedStatus->value,
        state: '{"user":"state"}',
        checkpoint: '{"checkpoint":"value"}',
        lockedUntil: 'lock-7'
    );

    $this->provider->shouldReceive('retrieve')->with($this->streamName)->andReturn($projection)->once();

    $loadedStatus = $this->projectionStore->loadStatus();

    expect($loadedStatus)->toBe($expectedStatus);
})->with([ProjectionStatus::cases()]);

it('return projection running status when projection not found', function () {
    $this->provider->shouldReceive('retrieve')
        ->with($this->streamName)
        ->andReturn(null)
        ->once();

    $loadedStatus = $this->projectionStore->loadStatus();

    expect($loadedStatus)->toBe(ProjectionStatus::RUNNING);
});

it('check if projection exists', function (bool $exists) {
    $this->provider->shouldReceive('exists')
        ->with($this->streamName)
        ->andReturn($exists);

    $result = $this->projectionStore->exists();

    expect($result)->toBe($exists);
})->with(['exists' => [true, false]]);

it('return current projection name', function () {
    expect($this->projectionStore->projectionName())->toBe($this->streamName);
});
