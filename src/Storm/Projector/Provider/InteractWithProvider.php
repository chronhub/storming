<?php

declare(strict_types=1);

namespace Storm\Projector\Provider;

use Storm\Contract\Projector\Repository;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Repository\ProjectionSnapshot;
use Storm\Projector\Workflow\Input\ResetSnapshot;
use Storm\Projector\Workflow\Input\TakeSnapshot;
use Storm\Projector\Workflow\Process;

use function in_array;

/**
 * @property-read Process $process
 * @property-read Repository $store
 *
 * @phpstan-require-implements PersistentProvider
 */
trait InteractWithProvider
{
    public function shouldUpdateLock(): void
    {
        $this->store->updateLock();
    }

    public function freed(): void
    {
        $this->store->release();

        $this->process->status()->set(ProjectionStatus::IDLE);
    }

    public function close(): void
    {
        $idleStatus = ProjectionStatus::IDLE;
        $snapshot = $this->takeSnapshot();

        $this->store->stop($snapshot, $idleStatus);

        $this->process->status()->set($idleStatus);
        $this->process->sprint()->halt();
    }

    public function restart(): void
    {
        $this->process->sprint()->continue();

        $runningStatus = ProjectionStatus::RUNNING;

        $this->store->startAgain($runningStatus);
        $this->process->status()->set($runningStatus);
    }

    public function disclose(): void
    {
        $disclosedStatus = $this->store->loadStatus();

        $this->process->status()->set($disclosedStatus);
    }

    public function synchronise(): void
    {
        $snapshot = $this->store->loadSnapshot();

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

            // todo check if Idle still needed
            $keepProjectionRunning = [ProjectionStatus::RUNNING, ProjectionStatus::IDLE];

            if (! in_array($this->process->status()->get(), $keepProjectionRunning, true)) {
                $this->process->sprint()->halt();
            }
        }
    }

    public function getName(): string
    {
        return $this->store->getName();
    }

    protected function mountProjection(): void
    {
        $currentStatus = $this->process->status()->get();

        if (! $this->store->exists()) {
            $this->store->create($currentStatus);
        }

        $runningStatus = ProjectionStatus::RUNNING;

        $this->store->start($runningStatus);
        $this->process->status()->set($runningStatus);
    }

    protected function takeSnapshot(): ProjectionSnapshot
    {
        return $this->process->call(new TakeSnapshot());
    }

    protected function resetSnapshot(): void
    {
        $this->process->call(new ResetSnapshot());
    }
}
