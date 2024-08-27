<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Subscription;

use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\ReadModel;
use Storm\Contract\Projector\Repository;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Provider\ReadingModelProvider;
use Storm\Projector\Provider\ReadModelProvider;
use Storm\Projector\Repository\ProjectionSnapshot;
use Storm\Projector\Workflow\Notification\Command\BatchStreamReset;
use Storm\Projector\Workflow\Notification\Command\CheckpointReset;
use Storm\Projector\Workflow\Notification\Command\EventStreamDiscovered;
use Storm\Projector\Workflow\Notification\Command\SprintStopped;
use Storm\Projector\Workflow\Notification\Command\UserStateRestored;
use Storm\Projector\Workflow\Notification\Promise\CurrentStatus;
use Storm\Projector\Workflow\Notification\Promise\IsBatchStreamLimitReached;

beforeEach(function () {
    $this->hub = mock(NotificationHub::class);
    $this->repository = mock(Repository::class);
    $this->expectation = new ManagementExpectation($this->repository, $this->hub);
    $this->readModel = mock(ReadModel::class);
    $this->management = new ReadingModelProvider($this->hub, $this->repository, $this->readModel);
});

dataset('checkpoints', [[[]], [[1, 5, 20]], [[1000, 5000]]]);
dataset('states', [[[]], [['foo']], [['bar']]]);
dataset('should create projection', [[false], [true]]);
dataset('should init read model', [[false], [true]]);

test('default instance', function () {
    expect($this->management)->toBeInstanceOf(ReadModelProvider::class);
});

test('rise projection', function (bool $alreadyCreated, bool $initReadModel, ProjectionStatus $currentStatus) {
    $this->expectation->assertMountProjection($alreadyCreated, $currentStatus);

    $this->readModel->expects('isInitialized')->andReturn($initReadModel);

    $initReadModel
        ? $this->readModel->shouldNotReceive('initialize')
        : $this->readModel->expects('initialize');

    $this->hub->expects('emit')->with(EventStreamDiscovered::class);

    $this->expectation->assertSynchronize();

    $this->management->rise();
})
    ->with('should create projection')
    ->with('should init read model')
    ->with('projection status');

test('store projection snapshot', function (array $checkpoint, array $state) {
    $this->expectation->assertProjectionStore($checkpoint, $state);

    $this->readModel->expects('persist');

    $this->management->store();
})
    ->with('checkpoints')
    ->with('states');

test('revise projection', function (array $checkpoint, array $state, ProjectionStatus $currentStatus) {
    $this->hub
        ->expects('emitMany')
        ->with(CheckpointReset::class, UserStateRestored::class);

    $this->expectation->assertProjectionSnapshot($checkpoint, $state);

    $this->hub->expects('await')->with(CurrentStatus::class)->andReturn($currentStatus);

    $this->repository->expects('reset')
        ->withArgs(
            fn (ProjectionSnapshot $result, ProjectionStatus $status) => $result->checkpoint === $checkpoint
                && $status === $currentStatus
        );

    $this->readModel->expects('reset');

    $this->management->revise();
})
    ->with('checkpoints')
    ->with('states')
    ->with('projection status');

test('discard projection', function (bool $withEmittedEvents) {
    $this->repository->expects('delete')->with($withEmittedEvents);

    $withEmittedEvents
        ? $this->readModel->expects('down')
        : $this->readModel->shouldNotReceive('down');

    $this->hub->expects('emit')->with(SprintStopped::class);
    $this->hub->expects('emitMany')->with(CheckpointReset::class, UserStateRestored::class);

    $this->management->discard($withEmittedEvents);
})->with('delete projection with emitted events');

test('persist when threshold is reached with read model', function (ProjectionStatus $currentStatus, ProjectionStatus $disclosedStatus) {
    $this->hub->expects('await')->with(IsBatchStreamLimitReached::class)->andReturn(true);

    $this->expectation->assertProjectionStore([[1, 5, 20]], ['foo']);
    $this->readModel->expects('persist');

    $this->hub->expects('emit')->with(BatchStreamReset::class);

    $this->expectation->assertDisclose($currentStatus, $disclosedStatus);

    $this->hub->expects('await')->with(CurrentStatus::class)->andReturn($disclosedStatus);

    $disclosedStatus !== ProjectionStatus::IDLE && $disclosedStatus !== ProjectionStatus::RUNNING
        ? $this->hub->expects('emit')->with(SprintStopped::class)
        : $this->hub->expects('emit')->with(SprintStopped::class)->never();

    $this->management->performWhenThresholdIsReached();
})
    ->with('projection status')
    ->with('projection status');

test('does not persist when threshold is not reached with read model', function () {
    $this->hub->expects('await')->with(IsBatchStreamLimitReached::class)->andReturn(false);

    $this->readModel->expects('persist')->never();
    $this->hub->expects('emit')->with(BatchStreamReset::class)->never();
    $this->hub->expects('emit')->with(SprintStopped::class)->never();

    $this->management->performWhenThresholdIsReached();
});

test('get read model', function () {
    expect($this->management->getReadModel())->toBe($this->readModel);
});
