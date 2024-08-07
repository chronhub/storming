<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Projector\ProjectionStatus;
use Storm\Projector\Provider\Events\ProjectionClosed;
use Storm\Projector\Provider\Events\ProjectionDiscarded;
use Storm\Projector\Provider\Events\ProjectionRestarted;
use Storm\Projector\Provider\Events\ProjectionRevised;
use Storm\Projector\Provider\Events\ProjectionStatusDisclosed;
use Storm\Projector\Provider\Events\ProjectionSynchronized;
use Storm\Projector\Workflow\Process;

/**
 * @property bool $onRise
 */
trait MonitorRemoteStatus
{
    /**
     * Disclose remote status and act accordingly.
     *
     * @return bool true if projection should stop on rise, false otherwise
     */
    protected function discloseRemoteStatus(Process $process): bool
    {
        $process->dispatch(new ProjectionStatusDisclosed());

        $currentStatus = $process->status()->get();

        return match ($currentStatus->value) {
            ProjectionStatus::STOPPING->value => $this->onStopping($process),
            ProjectionStatus::RESETTING->value => $this->onResetting($process),
            ProjectionStatus::DELETING->value => $this->onDeleting($process, false),
            ProjectionStatus::DELETING_WITH_EMITTED_EVENTS->value => $this->onDeleting($process, true),
            default => false,
        };
    }

    /**
     * Stop the projection on rise when stopping status is discovered.
     */
    protected function onStopping(Process $process): bool
    {
        if ($this->onRise) {
            $process->dispatch(new ProjectionSynchronized());
        }

        $process->dispatch(new ProjectionClosed());

        return $this->onRise;
    }

    /**
     * Always continue the projection when resetting.
     *
     * The projection will be restarted itself when the sprint
     * is running in the background.
     */
    protected function onResetting(Process $process): false
    {
        $process->dispatch(new ProjectionRevised());

        if (! $this->onRise && $process->sprint()->inBackground()) {
            $process->dispatch(new ProjectionRestarted());
        }

        return false;
    }

    /**
     * Stop the projection on rise when deleting.
     */
    protected function onDeleting(Process $process, bool $shouldDiscardEvents): bool
    {
        $process->dispatch(new ProjectionDiscarded($shouldDiscardEvents));

        return $this->onRise;
    }
}
