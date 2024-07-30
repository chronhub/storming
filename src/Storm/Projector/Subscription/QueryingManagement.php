<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Storm\Contract\Projector\QueryManagement;
use Storm\Projector\Workflow\WorkflowContext;

final readonly class QueryingManagement implements QueryManagement
{
    public function __construct(private WorkflowContext $workflowContext) {}

    public function performWhenThresholdIsReached(): void
    {
        if ($this->workflowContext->stat()->processed()->isLimitReached()) {
            $this->workflowContext->stat()->processed()->reset();
        }
    }
}
