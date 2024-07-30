<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Projector\Workflow\WorkflowContext;

final readonly class RiseQueryProjection
{
    public function __invoke(WorkflowContext $workflowContext): bool
    {
        if ($workflowContext->isFirstWorkflowCycle()) {
            $workflowContext->discoverEventStream();
        }

        return true;
    }
}
