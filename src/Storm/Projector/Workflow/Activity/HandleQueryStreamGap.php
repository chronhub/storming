<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Projector\Workflow\Notification\AfterHandleStreamGap;
use Storm\Projector\Workflow\Notification\BeforeHandleStreamGap;
use Storm\Projector\Workflow\WorkflowContext;

final class HandleQueryStreamGap
{
    public function __invoke(WorkflowContext $workflowContext): bool
    {
        $workflowContext->emit(BeforeHandleStreamGap::class);

        $hasGap = $workflowContext->recognition()->hasGap();

        if ($hasGap) {
            $workflowContext->recognition()->sleepOnGap();
        }

        $workflowContext->emit(AfterHandleStreamGap::class);

        return true;
    }
}
