<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Projector\Workflow\Notification\Management\ProjectionLockUpdated;
use Storm\Projector\Workflow\Notification\Management\ProjectionStored;
use Storm\Projector\Workflow\WorkflowContext;

final readonly class PersistOrUpdate
{
    /**
     * When running blank, we either update the lock after sleeping,
     * or, store the projection snapshot.
     */
    public function __invoke(WorkflowContext $workflowContext): bool
    {
        $hasGap = $workflowContext->recognition()->hasGap();

        if (! $hasGap) {
            if ($workflowContext->isBatchStreamBlank()) {
                $workflowContext->streamEvent()->sleep();

                $workflowContext->emit(new ProjectionLockUpdated());
            } else {
                $workflowContext->emit(new ProjectionStored());
            }
        }

        return true;
    }
}
