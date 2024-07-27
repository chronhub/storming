<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Repository\ProjectionSnapshot;
use Storm\Projector\Workflow\Notification\Command\BatchStreamReset;
use Storm\Projector\Workflow\Notification\Command\CheckpointReset;
use Storm\Projector\Workflow\Notification\Command\CheckpointUpdated;
use Storm\Projector\Workflow\Notification\Command\SprintContinue;
use Storm\Projector\Workflow\Notification\Command\SprintStopped;
use Storm\Projector\Workflow\Notification\Command\StatusChanged;
use Storm\Projector\Workflow\Notification\Command\StatusDisclosed;
use Storm\Projector\Workflow\Notification\Command\UserStateChanged;
use Storm\Projector\Workflow\Notification\Command\UserStateRestored;
use Storm\Projector\Workflow\Notification\Promise\CurrentFilteredCheckpoint;
use Storm\Projector\Workflow\Notification\Promise\CurrentStatus;
use Storm\Projector\Workflow\Notification\Promise\CurrentUserState;
use Storm\Projector\Workflow\Notification\Promise\IsBatchStreamLimitReached;

use function in_array;

trait InteractWithManagement
{
    public function shouldUpdateLock(): void
    {
        $this->projectionRepository->updateLock();
    }

    public function freed(): void
    {
        $this->projectionRepository->release();

        $this->onStatusChanged(ProjectionStatus::IDLE);
    }

    public function close(): void
    {
        $idleStatus = ProjectionStatus::IDLE;

        $this->projectionRepository->stop($this->takeSnapshot(), $idleStatus);

        $this->onStatusChanged($idleStatus);

        $this->hub->emit(SprintStopped::class);
    }

    public function restart(): void
    {
        $this->hub->emit(SprintContinue::class);

        $runningStatus = ProjectionStatus::RUNNING;

        $this->projectionRepository->startAgain($runningStatus);

        $this->onStatusChanged($runningStatus);
    }

    public function disclose(): void
    {
        $disclosedStatus = $this->projectionRepository->loadStatus();

        $this->hub->emit(
            StatusDisclosed::class,
            $disclosedStatus,
            $this->hub->await(CurrentStatus::class),
        );
    }

    public function synchronise(): void
    {
        $snapshot = $this->projectionRepository->loadSnapshot();

        $this->hub->emit(CheckpointUpdated::class, $snapshot->checkpoints);

        $state = $snapshot->userState;

        $this->hub->emitWhen(
            $state !== [],
            fn (NotificationHub $hub) => $hub->emit(UserStateChanged::class, $state)
        );
    }

    public function performWhenThresholdIsReached(): void
    {
        if ($this->hub->await(IsBatchStreamLimitReached::class)) {
            $this->store();

            $this->hub->emit(BatchStreamReset::class);

            $this->disclose();

            // todo check if Idle still needed
            $keepProjectionRunning = [ProjectionStatus::RUNNING, ProjectionStatus::IDLE];

            if (! in_array($this->hub->await(CurrentStatus::class), $keepProjectionRunning, true)) {
                $this->hub->emit(SprintStopped::class);
            }
        }
    }

    public function getName(): string
    {
        return $this->projectionRepository->projectionName();
    }

    public function hub(): NotificationHub
    {
        return $this->hub;
    }

    protected function mountProjection(): void
    {
        $this->hub->emit(SprintContinue::class);

        $currentStatus = $this->hub->await(CurrentStatus::class);

        if (! $this->projectionRepository->exists()) {
            $this->projectionRepository->create($currentStatus);
        }

        $runningStatus = ProjectionStatus::RUNNING;

        $this->projectionRepository->start($runningStatus);

        $this->onStatusChanged($runningStatus, $currentStatus);
    }

    protected function onStatusChanged(ProjectionStatus $newStatus, ?ProjectionStatus $previousStatus = null): void
    {
        $previousStatus = $previousStatus ?? $this->hub->await(CurrentStatus::class);

        $this->hub->emit(StatusChanged::class, $newStatus, $previousStatus);
    }

    protected function resetSnapshot(): void
    {
        $this->hub->emitMany(CheckpointReset::class, UserStateRestored::class);
    }

    protected function takeSnapshot(): ProjectionSnapshot
    {
        return new ProjectionSnapshot(
            $this->hub->await(CurrentFilteredCheckpoint::class),
            $this->hub->await(CurrentUserState::class)
        );
    }
}
