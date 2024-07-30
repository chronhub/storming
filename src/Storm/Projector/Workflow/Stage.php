<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow;

use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Workflow\Notification\BeforeWorkflowRenewal;
use Storm\Projector\Workflow\Notification\ResetOnlyOnceEmittedEvent;
use Storm\Projector\Workflow\Notification\ShouldTerminateWorkflow;
use Storm\Projector\Workflow\Notification\WorkflowRenewed;

class Stage
{
    /**
     * Starts the workflow.
     */
    public function beforeProcessing(WorkflowContext $workflowContext): void
    {
        $workflowContext->conditionallyStartWorkflow();
    }

    /**
     * After processing the workflow.
     */
    public function afterProcessing(WorkflowContext $workflowContext): void
    {
        // all listeners who want to stop the workflow after one completion
        // should react to this event
        // @example StopWhen reacting to this event
        $workflowContext->emit(ShouldTerminateWorkflow::class);

        $this->renew($workflowContext, $workflowContext->isSprintTerminated());
    }

    /**
     * Renews the workflow.
     */
    protected function renew(WorkflowContext $workflowContext, bool $isSprintTerminated): void
    {
        $workflowContext->emit(BeforeWorkflowRenewal::class);

        $this->resetOnCycleRenewed($workflowContext);

        if ($isSprintTerminated) {
            $this->resetOnTermination($workflowContext);
            $this->forgetOnTermination($workflowContext);
        } else {
            $workflowContext->incrementWorkflowCycle();
            $this->forgetOnCycleRenewed($workflowContext);
        }

        $workflowContext->emit(WorkflowRenewed::class);

        $workflowContext->emit(ResetOnlyOnceEmittedEvent::class);
    }

    protected function resetOnCycleRenewed(WorkflowContext $workflowContext): void
    {
        $workflowContext->stat()->processed()->reset();
    }

    protected function resetOnTermination(WorkflowContext $workflowContext): void
    {
        $workflowContext->stat()->cycle()->reset();
        $workflowContext->time()->reset();
        $workflowContext->stat()->main()->reset();
        $workflowContext->stat()->acked()->reset();
    }

    protected function forgetOnCycleRenewed(WorkflowContext $workflowContext): void
    {
        $workflowContext->forgetListener(GapType::RECOVERABLE_GAP->value);
        $workflowContext->forgetListener(GapType::UNRECOVERABLE_GAP->value);
    }

    protected function forgetOnTermination(WorkflowContext $workflowContext): void
    {
        //
    }
}
