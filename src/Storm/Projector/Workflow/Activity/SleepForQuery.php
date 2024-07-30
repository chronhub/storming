<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Projector\Workflow\WorkflowContext;

final readonly class SleepForQuery
{
    public function __invoke(WorkflowContext $workflowContext): bool
    {
        $hasGap = $workflowContext->recognition()->hasGap();

        if (! $hasGap) {
            $workflowContext->streamEvent()->sleep();
        }

        return true;
    }
}
