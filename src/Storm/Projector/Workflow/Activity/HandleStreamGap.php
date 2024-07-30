<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Projector\Subscription\InteractWithManagement;
use Storm\Projector\Workflow\Notification\AfterHandleStreamGap;
use Storm\Projector\Workflow\Notification\BeforeHandleStreamGap;
use Storm\Projector\Workflow\Notification\Management\ProjectionStored;
use Storm\Projector\Workflow\WorkflowContext;

final class HandleStreamGap
{
    /**
     * When a gap is detected, we first, sleep for a while,
     * to let the remote storage to fix it.
     * Then we store the projection if some stream events
     * still need to be processed before the gap detection.
     *
     * @see InteractWithManagement@persistWhenThresholdIsReached
     */
    public function __invoke(WorkflowContext $workflowContext): bool
    {
        $workflowContext->emit(BeforeHandleStreamGap::class);

        $hasGap = $workflowContext->recognition()->hasGap();

        if ($hasGap) {
            $workflowContext->recognition()->sleepOnGap();

            $isBatchStreamReset = $workflowContext->stat()->processed()->count() === 0;

            if (! $isBatchStreamReset) {
                $workflowContext->emit(new ProjectionStored());
            }
        }

        $workflowContext->emit(AfterHandleStreamGap::class);

        return true;
    }
}
