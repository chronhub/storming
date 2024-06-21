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
use Storm\Projector\Workflow\Notification\Sprint\IsSprintDaemonize;
use Storm\Projector\Workflow\Notification\Status\CurrentStatus;

trait MonitorRemoteStatus
{
    /**
     * Disclose remote status and act accordingly
     */
    protected function disclosedRemoteStatus(NotificationHub $hub): bool
    {
        $hub->trigger(new ProjectionStatusDisclosed());

        return match ($hub->expect(CurrentStatus::class)->value) {
            ProjectionStatus::STOPPING->value => $this->onStopping($hub),
            ProjectionStatus::RESETTING->value => $this->onResetting($hub),
            ProjectionStatus::DELETING->value => $this->onDeleting($hub, false),
            ProjectionStatus::DELETING_WITH_EMITTED_EVENTS->value => $this->onDeleting($hub, true),
            default => false,
        };
    }

    protected function onStopping(NotificationHub $hub): bool
    {
        if ($this->onRise) {
            $hub->trigger(new ProjectionSynchronized());
        }

        $hub->trigger(new ProjectionClosed());

        return $this->onRise;
    }

    protected function onResetting(NotificationHub $hub): bool
    {
        $hub->trigger(new ProjectionRevised());

        if (! $this->onRise && $hub->expect(IsSprintDaemonize::class)) {
            $hub->trigger(new ProjectionRestarted());
        }

        return false;
    }

    protected function onDeleting(NotificationHub $hub, bool $shouldDiscardEvents): bool
    {
        $hub->trigger(new ProjectionDiscarded($shouldDiscardEvents));

        return $this->onRise;
    }
}
