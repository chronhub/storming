<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow;

use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Workflow\Input\ConditionallyStartWorkflow;
use Storm\Projector\Workflow\Notification\BeforeWorkflowRenewal;
use Storm\Projector\Workflow\Notification\ResetOnlyOnceEmittedEvent;
use Storm\Projector\Workflow\Notification\ShouldTerminateWorkflow;
use Storm\Projector\Workflow\Notification\WorkflowRenewed;

/**
 * @deprecated
 */
class Stage
{
    public function __construct(protected readonly Process $process) {}

    public function beforeProcessing(): void
    {
        $this->process->call(new ConditionallyStartWorkflow);
    }

    public function afterProcessing(): void
    {
        $this->process->dispatch(ShouldTerminateWorkflow::class);

        $this->renewWorkflow($this->process->isSprintTerminated());
    }

    protected function renewWorkflow(bool $isSprintTerminated): void
    {
        $this->process->dispatch(BeforeWorkflowRenewal::class, $isSprintTerminated);

        $this->resetOnCycleRenewed();

        if ($isSprintTerminated) {
            $this->resetOnTermination();
            $this->forgetOnTermination();
        } else {
            $this->process->metrics()->increment('cycle');
            $this->forgetOnCycleRenewed();
        }

        $this->process->dispatch(WorkflowRenewed::class, $isSprintTerminated);
        $this->process->dispatch(ResetOnlyOnceEmittedEvent::class);
    }

    protected function resetOnCycleRenewed(): void
    {
        $this->process->metrics()->reset('processed');
    }

    protected function resetOnTermination(): void
    {
        $this->process->time()->reset();
        $this->process->metrics()->reset('cycle');
        $this->process->metrics()->reset('main');
        $this->process->metrics()->reset('acked');
    }

    protected function forgetOnCycleRenewed(): void
    {
        $this->process->removeListener(GapType::RECOVERABLE_GAP->value);
        $this->process->removeListener(GapType::UNRECOVERABLE_GAP->value);
    }

    protected function forgetOnTermination(): void
    {
        //
    }
}
