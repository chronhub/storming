<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Subscription;

use Closure;
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
    return function ($that) use ($alreadyCreated, $currentStatus) {
        $that->hub->shouldReceive('notify')->with(SprintContinue::class)->once();

        $newStatus = ProjectionStatus::RUNNING;
        $that->hub->shouldReceive('expect')->with(CurrentStatus::class)->andReturn($currentStatus);

        $that->repository->shouldReceive('exists')->once()->andReturn($alreadyCreated);

        ! $alreadyCreated
            ? $that->repository->shouldReceive('create')->with($currentStatus)->once()
            : $that->repository->shouldNotReceive('create');

        $that->repository->shouldReceive('start')->withArgs(
            fn (object $status) => $status === $newStatus
        );

        $that->hub->shouldReceive('notify')->with(StatusChanged::class, $newStatus, $currentStatus)->once();
    };
}

function assertSynchronize(): Closure
{
    return function ($that) {
        $result = new ProjectionResult([1, 5, 20], ['foo']);

        $that->repository->shouldReceive('loadDetail')->andReturn($result)->once();

        $that->hub->shouldReceive('notify')->with(CheckpointUpdated::class, $result->checkpoints)->once();

        $that->hub->shouldReceive('notify')->with(UserStateChanged::class, $result->userState)->once();
        $that->hub->shouldReceive('notifyWhen')->withArgs(function (bool $userStateNotEmpty, Closure $callback) use ($that) {
            $callback($that->hub);

            return $userStateNotEmpty === true;
        });
    };
}

function assertProjectionResult(array $checkpoint, array $state): Closure
{
    return function ($that) use ($checkpoint, $state) {
        $that->hub->shouldReceive('expect')->with(CurrentCheckpoint::class)->andReturn($checkpoint);
        $that->hub->shouldReceive('expect')->with(CurrentUserState::class)->andReturn($state);
    };
}

test('default instance', function () {
    expect($this->management)->toBeInstanceOf(ReadModelManagement::class);
});

test('rise projection', function (bool $alreadyCreated, bool $initReadModel, ProjectionStatus $currentStatus) {
    assertCreateProjection($alreadyCreated, $currentStatus)($this);

    $this->readModel->shouldReceive('isInitialized')->andReturn($initReadModel);

    $initReadModel
        ? $this->readModel->shouldNotReceive('initialize')
        : $this->readModel->shouldReceive('initialize')->once();

    $this->hub->shouldReceive('notify')->with(EventStreamDiscovered::class);

    assertSynchronize()($this);

    $this->management->rise();
})
    ->with('should create projection')
    ->with('should init read model')
    ->with('projection status');

test('store projection result', function (array $checkpoint, array $state) {
    assertProjectionResult($checkpoint, $state)($this);

    $this->repository->shouldReceive('persist')->withArgs(
        fn (ProjectionResult $result) => $result->checkpoints === $checkpoint && $result->userState === $state
    )->once();

    $this->readModel->shouldReceive('persist')->once();

    $this->management->store();
})
    ->with('checkpoints')
    ->with('states');

test('revise projection', function (array $checkpoint, array $state, ProjectionStatus $currentStatus) {
    $this->hub->shouldReceive('notifyMany')->with(CheckpointReset::class, UserStateRestored::class)->once();

    assertProjectionResult($checkpoint, $state)($this);

    $this->hub->shouldReceive('expect')->with(CurrentStatus::class)->andReturn($currentStatus);

    $this->repository->shouldReceive('reset')->withArgs(
        fn (ProjectionResult $result, object $status) => $result->checkpoints === $checkpoint && $status === $currentStatus
    )->once();

    $this->readModel->shouldReceive('reset')->once();

    $this->management->revise();
})
    ->with('checkpoints')
    ->with('states')
    ->with('projection status');

test('discard projection', function (bool $withEmittedEvents) {
    $this->repository->shouldReceive('delete')->with($withEmittedEvents)->once();

    $withEmittedEvents
        ? $this->readModel->shouldReceive('down')->once()
        : $this->readModel->shouldNotReceive('down');

    $this->hub->shouldReceive('notify')->with(SprintStopped::class)->once();

    $this->hub->shouldReceive('notifyMany')->with(CheckpointReset::class, UserStateRestored::class)->once();

    $this->management->discard($withEmittedEvents);
})->with([
    'with emitted events' => [true],
    'without emitted events' => [false],
]);

test('get read model', function () {
    expect($this->management->getReadModel())->toBe($this->readModel);
});
