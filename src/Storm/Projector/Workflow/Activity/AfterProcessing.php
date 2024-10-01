<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Closure;
use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Workflow\Notification\BeforeWorkflowRenewal;
use Storm\Projector\Workflow\Notification\ResetOnlyOnceEmittedEvent;
use Storm\Projector\Workflow\Notification\ShouldTerminateWorkflow;
use Storm\Projector\Workflow\Notification\WorkflowRenewed;
use Storm\Projector\Workflow\Process;

final class AfterProcessing
{
    public function __invoke(Process $process, Closure $next): Closure|bool
    {
        $process->dispatch(ShouldTerminateWorkflow::class);

        $this->renewWorkflow($process);

        return $next($process);
    }

    protected function renewWorkflow(Process $process): void
    {
        $isSprintTerminated = $process->isSprintTerminated();
        $process->dispatch(BeforeWorkflowRenewal::class, $isSprintTerminated);

        $this->resetOnCycleRenewed($process);

        if ($isSprintTerminated) {
            $this->resetOnTermination($process);
            $this->forgetOnTermination($process);
        } else {
            $process->metrics()->increment('cycle');
            $this->forgetOnCycleRenewed($process);
        }

        $process->dispatch(WorkflowRenewed::class, $isSprintTerminated);
        $process->dispatch(ResetOnlyOnceEmittedEvent::class);
    }

    protected function resetOnCycleRenewed(Process $process): void
    {
        $process->metrics()->reset('processed');
    }

    protected function resetOnTermination(Process $process): void
    {
        $process->time()->reset();
        $process->metrics()->reset('cycle');
        $process->metrics()->reset('main');
        $process->metrics()->reset('acked');
    }

    protected function forgetOnCycleRenewed(Process $process): void
    {
        $process->removeListener(GapType::RECOVERABLE_GAP->value);
        $process->removeListener(GapType::UNRECOVERABLE_GAP->value);
    }

    protected function forgetOnTermination(Process $process): void
    {
        //
    }
}
