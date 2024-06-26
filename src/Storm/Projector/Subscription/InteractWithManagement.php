<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Repository\ProjectionResult;
use Storm\Projector\Workflow\Notification\Batch\BatchReset;
use Storm\Projector\Workflow\Notification\Batch\IsBatchReached;
use Storm\Projector\Workflow\Notification\Checkpoint\CheckpointReset;
use Storm\Projector\Workflow\Notification\Checkpoint\CheckpointUpdated;
use Storm\Projector\Workflow\Notification\Checkpoint\CurrentCheckpoint;
use Storm\Projector\Workflow\Notification\Sprint\SprintContinue;
use Storm\Projector\Workflow\Notification\Sprint\SprintStopped;
use Storm\Projector\Workflow\Notification\Status\CurrentStatus;
use Storm\Projector\Workflow\Notification\Status\StatusChanged;
use Storm\Projector\Workflow\Notification\Status\StatusDisclosed;
use Storm\Projector\Workflow\Notification\UserState\CurrentUserState;
use Storm\Projector\Workflow\Notification\UserState\UserStateChanged;
use Storm\Projector\Workflow\Notification\UserState\UserStateRestored;

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
            $this->hub->expect(CurrentStatus::class),
            $disclosedStatus
        );
    }

    public function synchronise(): void
    {
        $projectionResult = $this->projectionRepository->loadDetail();

        $this->hub->notify(CheckpointUpdated::class, $projectionResult->checkpoints);

        $state = $projectionResult->userState;

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

        $currentStatus = $this->hub->expect(CurrentStatus::class);

        if (! $this->projectionRepository->exists()) {
            $this->projectionRepository->create($currentStatus);
        }

        $runningStatus = ProjectionStatus::RUNNING;

        $this->projectionRepository->start($runningStatus);

        $this->onStatusChanged($runningStatus, $currentStatus);
    }

    protected function resetState(): void
    {
        $this->hub->notifyMany(CheckpointReset::class, UserStateRestored::class);
    }

    protected function onStatusChanged(ProjectionStatus $newStatus, ?ProjectionStatus $previousStatus = null): void
    {
        $this->hub->notify(
            StatusChanged::class,
            $newStatus,
            $previousStatus ?? $this->hub->expect(CurrentStatus::class)
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
