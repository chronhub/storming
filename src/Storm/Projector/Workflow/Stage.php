<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\BeforeWorkflowRenewal;
use Storm\Projector\Workflow\Notification\Command\SprintTerminated;
use Storm\Projector\Workflow\Notification\Command\WorkflowCycleIncremented;
use Storm\Projector\Workflow\Notification\Command\WorkflowStarted;
use Storm\Projector\Workflow\Notification\ForgetOnCycleRenewed;
use Storm\Projector\Workflow\Notification\ForgetOnTermination;
use Storm\Projector\Workflow\Notification\Promise\IsSprintTerminated;
use Storm\Projector\Workflow\Notification\Promise\IsWorkflowStarted;
use Storm\Projector\Workflow\Notification\ResetOnCycleRenewed;
use Storm\Projector\Workflow\Notification\ResetOnlyOnceEmittedEvent;
use Storm\Projector\Workflow\Notification\ResetOnTermination;
use Storm\Projector\Workflow\Notification\ShouldTerminateWorkflow;
use Storm\Projector\Workflow\Notification\WorkflowRenewed;

class Stage
{
    /**
     * Starts the workflow.
     */
    public function beforeProcessing(NotificationHub $hub): void
    {
        $hub->emitWhen(
            ! $hub->await(IsWorkflowStarted::class),
            function (NotificationHub $hub) {
                $hub->emit(WorkflowStarted::class);
            }
        );
    }

    /**
     * After processing the workflow.
     */
    public function afterProcessing(NotificationHub $hub): void
    {
        $shouldTerminate = $this->shouldTerminate($hub);

        $this->renew($hub, $shouldTerminate);
    }

    /**
     * Should terminate the workflow.
     */
    protected function shouldTerminate(NotificationHub $hub): bool
    {
        $hub->emit(ShouldTerminateWorkflow::class);

        $isTerminated = $hub->await(IsSprintTerminated::class);

        if ($isTerminated) {
            $hub->emit(SprintTerminated::class);
        }

        return $hub->await(IsSprintTerminated::class);
    }

    /**
     * Renews the workflow.
     */
    protected function renew(NotificationHub $hub, bool $isSprintTerminated): void
    {
        $hub->emit(BeforeWorkflowRenewal::class);
        $hub->emit(ResetOnCycleRenewed::class);

        if ($isSprintTerminated) {
            $hub->emit(ResetOnTermination::class);
            $hub->emit(ForgetOnTermination::class);
        } else {
            $hub->emit(WorkflowCycleIncremented::class);
            $hub->emit(ForgetOnCycleRenewed::class);
        }

        $hub->emit(WorkflowRenewed::class);

        $hub->emit(ResetOnlyOnceEmittedEvent::class);
    }
}
