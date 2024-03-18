<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Checkpoint\Checkpoint;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Repository\ProjectionResult;
use Storm\Projector\Support\Notification\Batch\BatchReset;
use Storm\Projector\Support\Notification\Batch\IsBatchReached;
use Storm\Projector\Support\Notification\Checkpoint\CheckpointReset;
use Storm\Projector\Support\Notification\Checkpoint\CheckpointUpdated;
use Storm\Projector\Support\Notification\Checkpoint\CurrentCheckpoint;
use Storm\Projector\Support\Notification\Checkpoint\SnapshotTaken;
use Storm\Projector\Support\Notification\Sprint\SprintContinue;
use Storm\Projector\Support\Notification\Sprint\SprintStopped;
use Storm\Projector\Support\Notification\Status\CurrentStatus;
use Storm\Projector\Support\Notification\Status\StatusChanged;
use Storm\Projector\Support\Notification\Status\StatusDisclosed;
use Storm\Projector\Support\Notification\UserState\CurrentUserState;
use Storm\Projector\Support\Notification\UserState\UserStateChanged;
use Storm\Projector\Support\Notification\UserState\UserStateRestored;

use function in_array;

trait InteractWithManagement
{
    public function tryUpdateLock(): void
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

        $this->projectionRepository->stop($this->getProjectionResult(), $idleStatus);

        $this->onStatusChanged($idleStatus);

        $this->hub->expect(SprintStopped::class);
    }

    public function restart(): void
    {
        $this->hub->notify(SprintContinue::class);

        $runningStatus = ProjectionStatus::RUNNING;

        $this->projectionRepository->startAgain($runningStatus);

        $this->onStatusChanged($runningStatus);
    }

    public function disclose(): void
    {
        $disclosedStatus = $this->projectionRepository->loadStatus();

        $this->hub->notify(
            StatusDisclosed::class,
            $this->hub->expect(CurrentStatus::class), $disclosedStatus
        );
    }

    public function synchronise(): void
    {
        $projectionDetail = $this->projectionRepository->loadDetail();

        $this->hub->notify(CheckpointUpdated::class, $projectionDetail->checkpoints);

        $state = $projectionDetail->userState;

        $this->hub->notifyWhen(
            $state !== [],
            fn (NotificationHub $hub) => $hub->notify(UserStateChanged::class, $state)
        );
    }

    public function persistWhenThresholdIsReached(): void
    {
        if ($this->hub->expect(IsBatchReached::class)) {
            $this->store();

            $this->hub->notify(BatchReset::class);

            $this->disclose();

            // todo check if Idle still needed
            $keepProjectionRunning = [ProjectionStatus::RUNNING, ProjectionStatus::IDLE];

            if (! in_array($this->hub->expect(CurrentStatus::class), $keepProjectionRunning, true)) {
                $this->hub->notify(SprintStopped::class);
            }
        }
    }

    public function snapshot(Checkpoint $checkpoint): void
    {
        $this->snapshotRepository->snapshot($this->getName(), $checkpoint);

        $this->hub->notify(SnapshotTaken::class, $checkpoint);
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
        $this->hub->notify(SprintContinue::class);

        if (! $this->projectionRepository->exists()) {
            $this->projectionRepository->create($this->hub->expect(CurrentStatus::class));
        }

        $runningStatus = ProjectionStatus::RUNNING;

        $this->projectionRepository->start($runningStatus);

        $this->onStatusChanged($runningStatus);
    }

    protected function resetState(): void
    {
        $this->hub->notifyMany(CheckpointReset::class, UserStateRestored::class);
    }

    protected function onStatusChanged(ProjectionStatus $status): void
    {
        $this->hub->notify(
            StatusChanged::class,
            $this->hub->expect(CurrentStatus::class), $status
        );
    }

    protected function getProjectionResult(): ProjectionResult
    {
        return new ProjectionResult(
            $this->hub->expect(CurrentCheckpoint::class),
            $this->hub->expect(CurrentUserState::class)
        );
    }
}
