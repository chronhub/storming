<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Subscription;

use Closure;
use Mockery\MockInterface;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\Repository;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Repository\ProjectionSnapshot;
use Storm\Projector\Workflow\Notification\Command\CheckpointUpdated;
use Storm\Projector\Workflow\Notification\Command\SprintContinue;
use Storm\Projector\Workflow\Notification\Command\SprintStopped;
use Storm\Projector\Workflow\Notification\Command\StatusChanged;
use Storm\Projector\Workflow\Notification\Command\StatusDisclosed;
use Storm\Projector\Workflow\Notification\Command\UserStateChanged;
use Storm\Projector\Workflow\Notification\Promise\CurrentFilteredCheckpoint;
use Storm\Projector\Workflow\Notification\Promise\CurrentStatus;
use Storm\Projector\Workflow\Notification\Promise\CurrentUserState;
use Storm\Tests\Stubs\ProjectionSnapshotStub;

class ManagementExpectation
{
    public function __construct(
        protected Repository&MockInterface $repository,
        protected NotificationHub&MockInterface $hub,
    ) {}

    public function assertMountProjection(bool $isInitialized, ProjectionStatus $currentStatus): void
    {
        $this->hub->expects('emit')->with(SprintContinue::class);
        $this->hub->expects('await')->with(CurrentStatus::class)->andReturn($currentStatus);

        $this->repository->expects('exists')->andReturn($isInitialized);

        $isInitialized
            ? $this->repository->expects('create')->never()
            : $this->repository->expects('create')->withArgs(
                fn (ProjectionStatus $status) => $status === $currentStatus
            );

        $this->repository->expects('start')->withArgs(
            fn (ProjectionStatus $status) => $status === ProjectionStatus::RUNNING
        );

        $this->assertOnStatusChanged(ProjectionStatus::RUNNING, $currentStatus);
    }

    public function assertProjectionStore(array $checkpoint, array $state): void
    {
        $this->assertProjectionSnapshot($checkpoint, $state);

        $args = fn (ProjectionSnapshot $result) => $result->checkpoint === $checkpoint && $result->userState === $state;

        $this->repository->expects('persist')->withArgs($args);
    }

    public function assertSynchronize(): void
    {
        $result = (new ProjectionSnapshotStub())->fromDefault();

        $this->repository->expects('loadSnapshot')->andReturn($result);
        $this->hub->expects('emit')->with(CheckpointUpdated::class, $result->checkpoint);
        $this->hub->expects('emit')->with(UserStateChanged::class, $result->userState);

        $this->hub->expects('emitWhen')
            ->withArgs(function (bool $userStateNotEmpty, Closure $callback) {
                $callback($this->hub);

                return $userStateNotEmpty === true;
            });
    }

    public function assertClose(array $checkpoint, array $userState): void
    {
        $this->assertProjectionSnapshot($checkpoint, $userState);

        $this->repository->expects('stop')
            ->withArgs(
                fn (ProjectionSnapshot $snapshot, ProjectionStatus $status) => $snapshot->checkpoint === $checkpoint
                    && $snapshot->userState === $userState
                    && $status === ProjectionStatus::IDLE
            );

        $this->assertOnStatusChanged(ProjectionStatus::IDLE);

        $this->hub->expects('await')->with(SprintStopped::class);
    }

    public function assertDisclose(ProjectionStatus $currentStatus, ProjectionStatus $disclosedStatus): void
    {
        $this->repository->expects('loadStatus')->andReturn($disclosedStatus);

        $this->hub->expects('await')->with(CurrentStatus::class)->andReturn($currentStatus);

        $this->hub->expects('emit')->with(StatusDisclosed::class, $disclosedStatus, $currentStatus);
    }

    public function assertRestart(): void
    {
        $this->hub->expects('emit')->with(SprintContinue::class);

        $this->repository->expects('startAgain')->withArgs(
            fn (ProjectionStatus $status) => $status === ProjectionStatus::RUNNING
        );

        $this->assertOnStatusChanged(ProjectionStatus::RUNNING);
    }

    public function assertOnStatusChanged(ProjectionStatus $newStatus, ?ProjectionStatus $previousStatus = null): void
    {
        if ($previousStatus === null) {
            // assume running
            $previousStatus = ProjectionStatus::RUNNING;
            $this->hub->expects('await')->with(CurrentStatus::class)->andReturn($previousStatus);
        }

        $this->hub->expects('emit')->withArgs(
            function (string $notification, ProjectionStatus $new, ?ProjectionStatus $previous) use ($newStatus, $previousStatus) {
                return
                    $notification === StatusChanged::class
                    && $new === $newStatus
                    && $previous === $previousStatus;
            });
    }

    public function assertProjectionSnapshot(array $checkpoint, array $state): void
    {
        $this->hub->expects('await')->with(CurrentFilteredCheckpoint::class)->andReturn($checkpoint);
        $this->hub->expects('await')->with(CurrentUserState::class)->andReturn($state);
    }

    public function assertProjectionName(string $projectionName): void
    {
        $this->repository->expects('getName')->andReturn($projectionName);
    }
}
