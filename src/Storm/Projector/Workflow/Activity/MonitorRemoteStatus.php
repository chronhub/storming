<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Projector\ProjectionStatus;
use Storm\Projector\Workflow\Notification\Management\ProjectionClosed;
use Storm\Projector\Workflow\Notification\Management\ProjectionDiscarded;
use Storm\Projector\Workflow\Notification\Management\ProjectionRestarted;
use Storm\Projector\Workflow\Notification\Management\ProjectionRevised;
use Storm\Projector\Workflow\Notification\Management\ProjectionStatusDisclosed;
use Storm\Projector\Workflow\Notification\Management\ProjectionSynchronized;
use Storm\Projector\Workflow\WorkflowContext;

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
    protected function discloseRemoteStatus(WorkflowContext $workflowContext): bool
    {
        $workflowContext->emit(new ProjectionStatusDisclosed());

        $currentStatus = $workflowContext->status()->get();

        return match ($currentStatus->value) {
            ProjectionStatus::STOPPING->value => $this->onStopping($workflowContext),
            ProjectionStatus::RESETTING->value => $this->onResetting($workflowContext),
            ProjectionStatus::DELETING->value => $this->onDeleting($workflowContext, false),
            ProjectionStatus::DELETING_WITH_EMITTED_EVENTS->value => $this->onDeleting($workflowContext, true),
            default => false,
        };
    }

    /**
     * Stop the projection on rise when stopping status is discovered.
     */
    protected function onStopping(WorkflowContext $workflowContext): bool
    {
        if ($this->onRise) {
            $workflowContext->emit(new ProjectionSynchronized());
        }

        $workflowContext->emit(new ProjectionClosed());

        return $this->onRise;
    }

    /**
     * Always continue the projection when resetting.
     *
     * The projection will be restarted itself when the sprint
     * is running in the background.
     *
     * fixMe for emitter projector, unless it was emitted under the projection name
     *   we should not restart the projection, as emitted streams still exist
     */
    protected function onResetting(WorkflowContext $workflowContext): false
    {
        $workflowContext->emit(new ProjectionRevised());

        if (! $this->onRise && $workflowContext->sprint()->inBackground()) {
            $workflowContext->emit(new ProjectionRestarted());
        }

        return false;
    }

    /**
     * Stop the projection on rise when deleting.
     */
    protected function onDeleting(WorkflowContext $workflowContext, bool $shouldDiscardEvents): bool
    {
        $workflowContext->emit(new ProjectionDiscarded($shouldDiscardEvents));

        return $this->onRise;
    }
}
