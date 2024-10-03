<?php

declare(strict_types=1);

namespace Storm\Projector\Projection;

use Storm\Contract\Projector\Repository;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Store\ProjectionSnapshot;
use Storm\Projector\Workflow\Input\ResetSnapshot;
use Storm\Projector\Workflow\Input\TakeSnapshot;
use Storm\Projector\Workflow\Notification\BeforeWorkflowRenewal;
use Storm\Projector\Workflow\Process;

use function in_array;

/**
 * @property-read Process $process
 * @property-read Repository $repository
 *
 * @phpstan-require-implements PersistentProjection
 */
trait InteractWithProvider
{
    public function shouldUpdateLock(): void
    {
        $this->repository->updateLock();
    }

    public function freed(): void
    {
        $this->repository->release();

        $this->process->status()->set(ProjectionStatus::IDLE);
    }

    public function close(): void
    {
        $idleStatus = ProjectionStatus::IDLE;
        $snapshot = $this->takeSnapshot();

        $this->repository->stop($snapshot, $idleStatus);

        $this->process->status()->set($idleStatus);
        $this->process->sprint()->halt();

        // Attempt to fix the metrics for the current projection,
        // when the projection is stopped upfront from a signal handled by Symfony.
        // This is a workaround till a better signal handling is implemented.
        if (! $this->process->dispatcher()->wasEmittedOnce(BeforeWorkflowRenewal::class)) {
            $this->process->dispatch(new BeforeWorkflowRenewal(true));
        }
    }

    public function restart(): void
    {
        $this->process->sprint()->continue();

        $runningStatus = ProjectionStatus::RUNNING;

        $this->repository->startAgain($runningStatus);
        $this->process->status()->set($runningStatus);
    }

    public function disclose(): void
    {
        $disclosedStatus = $this->repository->loadStatus();

        $this->process->status()->set($disclosedStatus);
    }

    public function synchronise(): void
    {
        $snapshot = $this->repository->loadSnapshot();

        $this->process->recognition()->update($snapshot->checkpoint);

        $userState = $snapshot->userState;

        if ($userState !== []) {
            $this->process->userState()->put($userState);
        }
    }

    public function performWhenThresholdIsReached(): void
    {
        $thresholdReached = $this->process->metrics()->isProcessedThresholdReached();

        if ($thresholdReached) {
            $this->store();

            $this->process->metrics()->reset('processed');

            $this->disclose();

            $keepProjectionRunning = [ProjectionStatus::RUNNING, ProjectionStatus::IDLE];

            if (! in_array($this->process->status()->get(), $keepProjectionRunning, true)) {
                $this->process->sprint()->halt();
            }
        }
    }

    public function getName(): string
    {
        return $this->repository->getName();
    }

    protected function mountProjection(): void
    {
        $currentStatus = $this->process->status()->get();

        if (! $this->repository->exists()) {
            $this->repository->create($currentStatus);
        }

        $runningStatus = ProjectionStatus::RUNNING;

        $this->repository->start($runningStatus);
        $this->process->status()->set($runningStatus);
    }

    protected function takeSnapshot(): ProjectionSnapshot
    {
        return $this->process->call(new TakeSnapshot);
    }

    protected function resetSnapshot(): void
    {
        $this->process->call(new ResetSnapshot);
    }
}
