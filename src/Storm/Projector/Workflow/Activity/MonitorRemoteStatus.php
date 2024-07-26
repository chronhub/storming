<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Workflow\Notification\Management\ProjectionClosed;
use Storm\Projector\Workflow\Notification\Management\ProjectionDiscarded;
use Storm\Projector\Workflow\Notification\Management\ProjectionRestarted;
use Storm\Projector\Workflow\Notification\Management\ProjectionRevised;
use Storm\Projector\Workflow\Notification\Management\ProjectionStatusDisclosed;
use Storm\Projector\Workflow\Notification\Management\ProjectionSynchronized;
use Storm\Projector\Workflow\Notification\Promise\CurrentStatus;
use Storm\Projector\Workflow\Notification\Promise\IsSprintDaemonize;

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
    protected function discloseRemoteStatus(NotificationHub $hub): bool
    {
        $hub->emit(new ProjectionStatusDisclosed());

        return match ($hub->await(CurrentStatus::class)->value) {
            ProjectionStatus::STOPPING->value => $this->onStopping($hub),
            ProjectionStatus::RESETTING->value => $this->onResetting($hub),
            ProjectionStatus::DELETING->value => $this->onDeleting($hub, false),
            ProjectionStatus::DELETING_WITH_EMITTED_EVENTS->value => $this->onDeleting($hub, true),
            default => false,
        };
    }

    /**
     * Stop the projection on rise when stopping status is discovered.
     */
    protected function onStopping(NotificationHub $hub): bool
    {
        if ($this->onRise) {
            $hub->emit(new ProjectionSynchronized());
        }

        $hub->emit(new ProjectionClosed());

        return $this->onRise;
    }

    /**
     * Always continue the projection when resetting.
     *
     * The projection will be restarted itself when the sprint
     * is running in the background.
     */
    protected function onResetting(NotificationHub $hub): false
    {
        $hub->emit(new ProjectionRevised());

        if (! $this->onRise && $hub->await(IsSprintDaemonize::class)) {
            $hub->emit(new ProjectionRestarted());
        }

        return false;
    }

    /**
     * Stop the projection on rise when deleting.
     */
    protected function onDeleting(NotificationHub $hub, bool $shouldDiscardEvents): bool
    {
        $hub->emit(new ProjectionDiscarded($shouldDiscardEvents));

        return $this->onRise;
    }
}
