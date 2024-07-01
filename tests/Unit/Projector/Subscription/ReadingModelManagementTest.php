<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Subscription;

use Closure;
use Mockery\MockInterface;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\ProjectionRepository;
use Storm\Contract\Projector\ReadModel;
use Storm\Contract\Projector\ReadModelManagement;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Repository\ProjectionResult;
use Storm\Projector\Subscription\ReadingModelManagement;
use Storm\Projector\Workflow\Notification\Checkpoint\CheckpointReset;
use Storm\Projector\Workflow\Notification\Checkpoint\CheckpointUpdated;
use Storm\Projector\Workflow\Notification\Checkpoint\CurrentCheckpoint;
use Storm\Projector\Workflow\Notification\Sprint\SprintContinue;
use Storm\Projector\Workflow\Notification\Sprint\SprintStopped;
use Storm\Projector\Workflow\Notification\Status\CurrentStatus;
use Storm\Projector\Workflow\Notification\Status\StatusChanged;
use Storm\Projector\Workflow\Notification\Stream\EventStreamDiscovered;
use Storm\Projector\Workflow\Notification\UserState\CurrentUserState;
use Storm\Projector\Workflow\Notification\UserState\UserStateChanged;
use Storm\Projector\Workflow\Notification\UserState\UserStateRestored;
use Storm\Tests\Stubs\ProjectionResultStub;

beforeEach(function () {
    $this->hub = mock(NotificationHub::class);
    $this->repository = mock(ProjectionRepository::class);
    $this->readModel = mock(ReadModel::class);
    $this->management = new ReadingModelManagement($this->hub, $this->repository, $this->readModel);
});

dataset('checkpoints', [[[]], [[1, 5, 20]], [[1000, 5000]]]);
dataset('states', [[[]], [['foo']], [['bar']]]);
dataset('should create projection', [[false], [true]]);
dataset('should init read model', [[false], [true]]);

function assertCreateProjection(bool $alreadyCreated, ProjectionStatus $currentStatus): Closure
{
    return function (ProjectionRepository&MockInterface $repository, NotificationHub&MockInterface $hub) use ($alreadyCreated, $currentStatus) {
        $hub->expects('notify')->with(SprintContinue::class);

        $newStatus = ProjectionStatus::RUNNING;
        $hub->expects('expect')->with(CurrentStatus::class)->andReturn($currentStatus);

        $repository->expects('exists')->andReturn($alreadyCreated);

        ! $alreadyCreated
            ? $repository->expects('create')->with($currentStatus)
            : $repository->shouldNotReceive('create');

        $repository->expects('start')->withArgs(fn (ProjectionStatus $status) => $status === $newStatus);
        $hub->expects('notify')->with(StatusChanged::class, $newStatus, $currentStatus);
    };
}

function assertSynchronize(): Closure
{
    return function (ProjectionRepository&MockInterface $repository, NotificationHub&MockInterface $hub) {
        $result = (new ProjectionResultStub())->fromDefault();

        $repository->expects('loadDetail')->andReturn($result);
        $hub->expects('notify')->with(CheckpointUpdated::class, $result->checkpoints);
        $hub->expects('notify')->with(UserStateChanged::class, $result->userState);
        $hub->expects('notifyWhen')
            ->withArgs(function (bool $userStateNotEmpty, Closure $callback) use ($hub) {
                $callback($hub);

                return $userStateNotEmpty === true;
            });
    };
}

function assertProjectionResult(array $checkpoint, array $state): Closure
{
    return function (NotificationHub&MockInterface $hub) use ($checkpoint, $state) {
        $hub->expects('expect')->with(CurrentCheckpoint::class)->andReturn($checkpoint);
        $hub->expects('expect')->with(CurrentUserState::class)->andReturn($state);
    };
}

test('default instance', function () {
    expect($this->management)->toBeInstanceOf(ReadModelManagement::class);
});

test('rise projection', function (bool $alreadyCreated, bool $initReadModel, ProjectionStatus $currentStatus) {
    assertCreateProjection($alreadyCreated, $currentStatus)($this->repository, $this->hub);

    $this->readModel->expects('isInitialized')->andReturn($initReadModel);

    $initReadModel
        ? $this->readModel->shouldNotReceive('initialize')
        : $this->readModel->expects('initialize');

    $this->hub->expects('notify')->with(EventStreamDiscovered::class);

    assertSynchronize()($this->repository, $this->hub);

    $this->management->rise();
})
    ->with('should create projection')
    ->with('should init read model')
    ->with('projection status');

test('store projection result', function (array $checkpoint, array $state) {
    assertProjectionResult($checkpoint, $state)($this->hub);

    $this->repository
        ->expects('persist')
        ->withArgs(
            fn (ProjectionResult $result) => $result->checkpoints === $checkpoint
                && $result->userState === $state
        );

    $this->readModel->expects('persist');

    $this->management->store();
})
    ->with('checkpoints')
    ->with('states');

test('revise projection', function (array $checkpoint, array $state, ProjectionStatus $currentStatus) {
    $this->hub->expects('notifyMany')
        ->with(CheckpointReset::class, UserStateRestored::class);

    assertProjectionResult($checkpoint, $state)($this->hub);

    $this->hub->expects('expect')->with(CurrentStatus::class)->andReturn($currentStatus);

    $this->repository
        ->expects('reset')
        ->withArgs(
            fn (ProjectionResult $result, ProjectionStatus $status) => $result->checkpoints === $checkpoint
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

    $this->hub->expects('notify')->with(SprintStopped::class);
    $this->hub->expects('notifyMany')->with(CheckpointReset::class, UserStateRestored::class);

    $this->management->discard($withEmittedEvents);
})->with('delete projection with emitted events');

test('get read model', function () {
    expect($this->management->getReadModel())->toBe($this->readModel);
});
