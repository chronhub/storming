<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector;

use InvalidArgumentException;
use Storm\Contract\Projector\ProjectionModel;
use Storm\Contract\Projector\ProjectionProvider;
use Storm\Contract\Serializer\SymfonySerializer;
use Storm\Projector\Exception\ProjectionFailed;
use Storm\Projector\Exception\ProjectionNotFound;
use Storm\Projector\Monitor;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Store\Data\UpdateStatusData;

use function json_encode;

beforeEach(function () {
    $this->projectionProvider = mock(ProjectionProvider::class);
    $this->serializer = mock(SymfonySerializer::class);
    $this->projectorSupervisor = new Monitor($this->projectionProvider, $this->serializer);
    $this->projectionName = 'some_projection_name';
});

dataset('monitor projection status', [
    [ProjectionStatus::STOPPING],
    [ProjectionStatus::RESETTING],
    [ProjectionStatus::DELETING],
    [ProjectionStatus::DELETING_WITH_EMITTED_EVENTS],
]);

function assertProjectionUpdated($that, ProjectionStatus $expectedStatus): void
{
    $that->projectionProvider->expects('updateProjection')->withArgs(
        fn (string $name, UpdateStatusData $data) => $name === $that->projectionName
            && $data->status === $expectedStatus->value
    );
}

function callMonitorSupervisor($that, ProjectionStatus $status): void
{
    switch ($status) {
        case ProjectionStatus::STOPPING:
            $that->projectorSupervisor->markAsStop($that->projectionName);

            break;
        case ProjectionStatus::RESETTING:
            $that->projectorSupervisor->markAsReset($that->projectionName);

            break;
        case ProjectionStatus::DELETING:
            $that->projectorSupervisor->markAsDelete($that->projectionName, false);

            break;
        case ProjectionStatus::DELETING_WITH_EMITTED_EVENTS:
            $that->projectorSupervisor->markAsDelete($that->projectionName, true);

            break;
        default:
            throw new InvalidArgumentException('Invalid testing projection status');
    }
}

test('should mark projection with status', function (ProjectionStatus $status) {
    assertProjectionUpdated($this, $status);

    callMonitorSupervisor($this, $status);
})->with('monitor projection status');

test('raise same exception when projection not found on update status', function (ProjectionStatus $status) {
    $exception = new ProjectionNotFound('Projection not found');
    $this->projectionProvider->expects('updateProjection')->andThrows($exception);

    try {
        callMonitorSupervisor($this, $status);
    } catch (ProjectionNotFound $e) {
        expect($e)->toBe($exception);
    }
})->with('monitor projection status');

test('raise same exception when projection failed on update status', function (ProjectionStatus $status) {
    $exception = new ProjectionFailed('Failed to update projection status');
    $this->projectionProvider->expects('updateProjection')->andThrows($exception);

    try {
        callMonitorSupervisor($this, $status);
    } catch (ProjectionFailed $e) {
        expect($e)->toBe($exception);
    }
})->with('monitor projection status');

test('encapsulate other exceptions raise on update status', function (ProjectionStatus $status) {
    $exception = new InvalidArgumentException('Some error', 500);
    $this->projectionProvider->expects('updateProjection')->andThrows($exception);

    try {
        callMonitorSupervisor($this, $status);
    } catch (ProjectionFailed $e) {
        expect($exception)->toBe($e->getPrevious())
            ->and($e->getMessage())->toBe($exception->getMessage())
            ->and($e->getCode())->toBe($exception->getCode());
    }
})->with('monitor projection status');

test('get status of projection', function (ProjectionStatus $status) {
    $model = mock(ProjectionModel::class);
    $model->expects('status')->andReturn($status->value);
    $this->projectionProvider->expects('retrieve')->with($this->projectionName)->andReturn($model);

    expect($this->projectorSupervisor->statusOf($this->projectionName))->toBe($status->value);
})->with('projection status');

test('get checkpoint of projection', function () {
    $checkpoint = ['some' => 'data'];
    $model = mock(ProjectionModel::class);
    $model->expects('checkpoint')->andReturn(json_encode($checkpoint));

    $this->projectionProvider->expects('retrieve')->andReturn($model);

    $this->serializer->expects('decode')->with(json_encode($checkpoint), 'json')->andReturn($checkpoint);

    expect($this->projectorSupervisor->checkpointOf($this->projectionName))->toBe($checkpoint);
});

test('get state of projection', function () {
    $state = ['some' => 'data'];
    $model = mock(ProjectionModel::class);
    $model->expects('state')->andReturn(json_encode($state));

    $this->projectionProvider->expects('retrieve')->andReturn($model);

    $this->serializer->expects('decode')->with(json_encode($state), 'json')->andReturn($state);

    expect($this->projectorSupervisor->stateOf($this->projectionName))->toBe($state);
});

describe('raise projection not found', function () {
    test('of checkpoint of unknown projection', function () {
        $this->projectionProvider->expects('retrieve')->with($this->projectionName)->andReturnNull();

        expect($this->projectorSupervisor->checkpointOf($this->projectionName));
    })->throws(ProjectionNotFound::class);

    test('of state of unknown projection', function () {
        $this->projectionProvider->expects('retrieve')->with($this->projectionName)->andReturnNull();

        expect($this->projectorSupervisor->stateOf($this->projectionName));
    })->throws(ProjectionNotFound::class);

    test('of status of unknown projection', function () {
        $this->projectionProvider->expects('retrieve')->with($this->projectionName)->andReturnNull();

        $this->projectorSupervisor->statusOf($this->projectionName);
    })->throws(ProjectionNotFound::class);
});

test('filter projection by stream names', function (array $streamNames) {
    $this->projectionProvider->expects('filterByNames')->with(...$streamNames)->andReturn($streamNames);

    expect($this->projectorSupervisor->filterNames(...$streamNames))->toBe($streamNames);
})->with([
    [['stream1', 'stream2']],
    [['stream1']],
    [['stream2']],
    [[]],
]);

test('check if projection exists', function (bool $exists) {
    $this->projectionProvider->expects('exists')->with($this->projectionName)->andReturn($exists);

    expect($this->projectorSupervisor->exists($this->projectionName))->toBe($exists);
})->with('projection exists');
