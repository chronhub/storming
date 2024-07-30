<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Storm\Projector\Workflow\WorkflowContext;

interface ShouldAgentSubscribe
{
    /**
     * Subscribe to the notification hub.
     */
    public function subscribe(WorkflowContext $workflowContext, ContextReader $context): void;
}
