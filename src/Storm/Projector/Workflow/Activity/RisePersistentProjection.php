<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Projector\Workflow\Notification\Management\ProjectionRise;
use Storm\Projector\Workflow\WorkflowContext;

final readonly class RisePersistentProjection
{
    use MonitorRemoteStatus;

    protected bool $onRise;

    public function __construct()
    {
        $this->onRise = true;
    }

    public function __invoke(WorkflowContext $workflowContext): bool
    {
        if ($workflowContext->isFirstWorkflowCycle()) {
            if ($this->discloseRemoteStatus($workflowContext)) {
                return false;
            }

            $workflowContext->emit(new ProjectionRise());
        }

        return true;
    }
}
