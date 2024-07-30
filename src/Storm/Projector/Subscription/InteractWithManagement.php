<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Storm\Projector\ProjectionStatus;

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

        $this->workflowContext->status()->set(ProjectionStatus::IDLE);
    }

    public function close(): void
    {
        $idleStatus = ProjectionStatus::IDLE;
        $snapshot = $this->workflowContext->takeSnapshot();

        $this->projectionRepository->stop($snapshot, $idleStatus);
        $this->workflowContext->status()->set($idleStatus);
        $this->workflowContext->sprint()->halt();
    }

    public function restart(): void
    {
        $this->workflowContext->sprint()->continue();

        $runningStatus = ProjectionStatus::RUNNING;

        $this->projectionRepository->startAgain($runningStatus);
        $this->workflowContext->status()->set($runningStatus);
    }

    public function disclose(): void
    {
        $disclosedStatus = $this->projectionRepository->loadStatus();

        $this->workflowContext->status()->set($disclosedStatus);
    }

    public function synchronise(): void
    {
        $snapshot = $this->projectionRepository->loadSnapshot();

        $this->workflowContext->recognition()->update($snapshot->checkpoints);

        $state = $snapshot->userState;

        if ($state !== []) {
            $this->workflowContext->userState()->put($state);
        }
    }

    public function performWhenThresholdIsReached(): void
    {
        $thresholdReached = $this->workflowContext->stat()->processed()->isLimitReached();

        if ($thresholdReached) {
            $this->store();

            $this->workflowContext->stat()->processed()->reset();

            $this->disclose();

            // todo check if Idle still needed
            $keepProjectionRunning = [ProjectionStatus::RUNNING, ProjectionStatus::IDLE];

            if (! in_array($this->workflowContext->status()->get(), $keepProjectionRunning, true)) {
                $this->workflowContext->sprint()->halt();
            }
        }
    }

    public function getName(): string
    {
        return $this->projectionRepository->getName();
    }

    protected function mountProjection(): void
    {
        $currentStatus = $this->workflowContext->status()->get();

        if (! $this->projectionRepository->exists()) {
            $this->projectionRepository->create($currentStatus);
        }

        $runningStatus = ProjectionStatus::RUNNING;

        $this->projectionRepository->start($runningStatus);
        $this->workflowContext->status()->set($runningStatus);
    }
}
