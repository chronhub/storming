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
    $this->provider = $this->createMock(ProjectionProvider::class);
    $this->lockManager = $this->createMock(LockManager::class);

    $this->serializer = new Serializer([], [new JsonEncoder()]);
    $this->streamName = 'stream-1';

    $this->projectionStore = new ProjectionStore(
        $this->provider,
        $this->lockManager,
        $this->serializer,
        $this->streamName
    );
});

it('create projection with status', function (ProjectionStatus $status) {
    $this->provider->expects($this->once())
        ->method('createProjection')
        ->with($this->streamName, $this->callback(function ($data) use ($status) {
            return $data instanceof CreateData && $data->status === $status->value;
        }));

    $this->projectionStore->create($status);
})->with([ProjectionStatus::cases()]);

it('start projection with status', function (ProjectionStatus $status) {
    $this->lockManager->expects($this->once())
        ->method('acquire')
        ->willReturn('lock-1');

    $this->provider->expects($this->once())
        ->method('acquireLock')
        ->with($this->streamName, $this->callback(function ($data) use ($status) {
            return $data instanceof StartData && $data->status === $status->value && $data->lockedUntil === 'lock-1';
        }));

    $this->projectionStore->start($status);
})->with([ProjectionStatus::cases()]);

it('stop projection with result and status', function (ProjectionStatus $status) {
    $projectionResult = new ProjectionResult(
        checkpoints: ['checkpoint' => 'value'],
        userState: ['user' => 'state']
    );

    $this->lockManager->expects($this->once())->method('refresh')->willReturn('lock-2');

    $this->provider->expects($this->once())
        ->method('updateProjection')
        ->with($this->streamName, $this->callback(function ($data) use ($status) {
            return $data instanceof StopData
                && $data->status === $status->value
                && $data->state === '{"user":"state"}'
                && $data->checkpoint === '{"checkpoint":"value"}'
                && $data->lockedUntil === 'lock-2';
        }));

    $this->projectionStore->stop($projectionResult, $status);
})->with([ProjectionStatus::cases()]);

it('release projection', function () {
    $this->provider->expects($this->once())
        ->method('updateProjection')
        ->with($this->streamName, $this->callback(function ($data) {
            return $data instanceof ReleaseData && $data->status === ProjectionStatus::IDLE->value;
        }));

    $this->projectionStore->release();
});

it('start again projection with status', function (ProjectionStatus $status) {
    $this->lockManager->expects($this->once())
        ->method('acquire')
        ->willReturn('lock-3');

    $this->provider->expects($this->once())
        ->method('updateProjection')
        ->with($this->streamName, $this->callback(function ($data) use ($status) {
            return $data instanceof StartAgainData && $data->status === $status->value && $data->lockedUntil === 'lock-3';
        }));

    $this->projectionStore->startAgain($status);
})->with([ProjectionStatus::cases()]);

it('persist projection with result', function () {
    $projectionResult = new ProjectionResult(
        checkpoints: ['checkpoint' => 'value'],
        userState: ['user' => 'state']
    );

    $this->lockManager->expects($this->once())->method('refresh')->willReturn('lock-4');

    $this->provider->expects($this->once())
        ->method('updateProjection')
        ->with($this->streamName, $this->callback(function ($data) {
            return $data instanceof PersistData
                && $data->state === '{"user":"state"}'
                && $data->checkpoint === '{"checkpoint":"value"}'
                && $data->lockedUntil === 'lock-4';
        }));

    $this->projectionStore->persist($projectionResult);
});

it('reset projection with result and status', function (ProjectionStatus $status) {
    $projectionResult = new ProjectionResult(
        checkpoints: ['checkpoint' => 'value'],
        userState: ['user' => 'state']
    );

    $this->provider->expects($this->once())
        ->method('updateProjection')
        ->with($this->streamName, $this->callback(function ($data) use ($status) {
            return $data instanceof ResetData
                && $data->status === $status->value
                && $data->state === '{"user":"state"}'
                && $data->checkpoint === '{"checkpoint":"value"}';
        }));

    $this->projectionStore->reset($projectionResult, $status);
})->with([ProjectionStatus::cases()]);

it('delete projection', function (bool $withEmittedEvents) {
    $this->provider->expects($this->once())
        ->method('deleteProjection')
        ->with($this->streamName);

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

    $this->provider->expects($this->once())
        ->method('retrieve')
        ->with($this->streamName)
        ->willReturn($projection);

    $result = $this->projectionStore->loadDetail();

    expect($result->checkpoints)->toBe(['checkpoint' => 'value'])
        ->and($result->userState)->toBe(['user' => 'state']);
})->with([ProjectionStatus::cases()]);

it('update projection lock', function () {
    $this->lockManager->expects($this->once())
        ->method('shouldRefresh')
        ->willReturn(true);

    $this->lockManager->expects($this->once())
        ->method('refresh')
        ->willReturn('lock-6');

    $this->provider->expects($this->once())
        ->method('updateProjection')
        ->with($this->streamName, $this->callback(function ($data) {
            return $data instanceof UpdateLockData && $data->lockedUntil === 'lock-6';
        }));

    $this->projectionStore->updateLock();
});

it('does not update projection lock when should refresh return false', function () {
    $this->lockManager->expects($this->once())->method('shouldRefresh')->willReturn(false);
    $this->lockManager->expects($this->never())->method('refresh');
    $this->provider->expects($this->never())->method('updateProjection');

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

    $this->provider->expects($this->once())
        ->method('retrieve')
        ->with($this->streamName)
        ->willReturn($projection);

    $loadStatus = $this->projectionStore->loadStatus();

    expect($loadStatus)->toBe($expectedStatus);
})->with([ProjectionStatus::cases()]);

it('return projection running status when projection not found', function () {
    $this->provider->expects($this->once())
        ->method('retrieve')
        ->with($this->streamName)
        ->willReturn(null);

    $loadStatus = $this->projectionStore->loadStatus();

    expect($loadStatus)->toBe(ProjectionStatus::RUNNING);
});

it('check if projection exists', function (bool $exists) {
    $this->provider->expects($this->once())
        ->method('exists')
        ->with($this->streamName)
        ->willReturn($exists);

    $result = $this->projectionStore->exists();

    expect($result)->toBe($exists);
})->with(['exists' => [true, false]]);

it('return current projection name', function () {
    expect($this->projectionStore->projectionName())->toBe($this->streamName);
});
