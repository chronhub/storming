<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow;

use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Workflow\Input\ConditionallyStartWorkflow;
use Storm\Projector\Workflow\Input\IsSprintTerminated;
use Storm\Projector\Workflow\Notification\BeforeWorkflowRenewal;
use Storm\Projector\Workflow\Notification\ResetOnlyOnceEmittedEvent;
use Storm\Projector\Workflow\Notification\ShouldTerminateWorkflow;
use Storm\Projector\Workflow\Notification\WorkflowRenewed;

class Stage
{
    public function __construct(
        protected readonly Process $process
    ) {}

    public function beforeProcessing(): void
    {
        $this->process->call(new ConditionallyStartWorkflow());
    }

    public function afterProcessing(): void
    {
        $this->process->dispatch(ShouldTerminateWorkflow::class);

        $this->renew($this->process->call(new IsSprintTerminated()));
    }

    /**
     * Renews the workflow.
     */
    protected function renew(bool $isSprintTerminated): void
    {
        $this->process->dispatch(BeforeWorkflowRenewal::class);

        $this->resetOnCycleRenewed();

        if ($isSprintTerminated) {
            $this->resetOnTermination();
            $this->forgetOnTermination();
        } else {
            $this->process->metrics()->cycle++;
            $this->forgetOnCycleRenewed();
        }

        $this->process->dispatch(WorkflowRenewed::class);
        $this->process->dispatch(ResetOnlyOnceEmittedEvent::class);
    }

    protected function resetOnCycleRenewed(): void
    {
        $this->process->metrics()['processed'] = 0;
    }

    protected function resetOnTermination(): void
    {
        $this->process->time()->reset();

        $this->process->metrics()['cycle'] = 0;
        $this->process->metrics()['main'] = 0;
        $this->process->metrics()['acked'] = 0;
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
