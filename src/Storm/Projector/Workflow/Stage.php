<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\BeforeWorkflowRenewal;
use Storm\Projector\Workflow\Notification\Command\BatchStreamReset;
use Storm\Projector\Workflow\Notification\Command\MainCounterReset;
use Storm\Projector\Workflow\Notification\Command\NewEventStreamReset;
use Storm\Projector\Workflow\Notification\Command\SprintTerminated;
use Storm\Projector\Workflow\Notification\Command\StreamEventAckedReset;
use Storm\Projector\Workflow\Notification\Command\TimeReset;
use Storm\Projector\Workflow\Notification\Command\WorkflowCycleIncremented;
use Storm\Projector\Workflow\Notification\Command\WorkflowCycleReset;
use Storm\Projector\Workflow\Notification\Command\WorkflowStarted;
use Storm\Projector\Workflow\Notification\Promise\IsSprintTerminated;
use Storm\Projector\Workflow\Notification\Promise\IsWorkflowStarted;
use Storm\Projector\Workflow\Notification\Promise\StreamEventProcessed;
use Storm\Projector\Workflow\Notification\RecoverableGapDetected;
use Storm\Projector\Workflow\Notification\ResetOnlyOnceEmittedEvent;
use Storm\Projector\Workflow\Notification\ShouldTerminateWorkflow;
use Storm\Projector\Workflow\Notification\UnrecoverableGapDetected;
use Storm\Projector\Workflow\Notification\WorkflowRenewed;

class Stage
{
    protected array $resetsOnCycleRenewed = [
        BatchStreamReset::class,
        NewEventStreamReset::class,
    ];

    protected array $resetsOnTermination = [
        WorkflowCycleReset::class,
        TimeReset::class,
        MainCounterReset::class,
        StreamEventAckedReset::class,
    ];

    protected array $forgetsOnCycleRenewed = [
        StreamEventProcessed::class,
        RecoverableGapDetected::class,
        UnrecoverableGapDetected::class,
    ];

    protected array $forgetsOnTermination = [];

    /**
     * Starts the workflow.
     */
    public function beforeProcessing(NotificationHub $hub): void
    {
        if (! $hub->await(IsWorkflowStarted::class)) {
            $hub->emit(WorkflowStarted::class);
        }
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

        $this->resetOnCycleRenewed($hub);

        if ($isSprintTerminated) {
            $this->resetOnTermination($hub);
            $this->forgetOnTermination($hub);
        } else {
            $hub->emit(WorkflowCycleIncremented::class);
            $this->forgetOnCycleRenewed($hub);
        }

        $hub->emit(WorkflowRenewed::class);

        $hub->emit(ResetOnlyOnceEmittedEvent::class);
    }

    protected function resetOnCycleRenewed(NotificationHub $hub): void
    {
        foreach ($this->resetsOnCycleRenewed as $listener) {
            $hub->emit($listener);
        }
    }

    protected function resetOnTermination(NotificationHub $hub): void
    {
        foreach ($this->resetsOnTermination as $listener) {
            $hub->emit($listener);
        }
    }

    protected function forgetOnCycleRenewed(NotificationHub $hub): void
    {
        foreach ($this->forgetsOnCycleRenewed as $listener) {
            $hub->forgetEvent($listener);
        }
    }

    protected function forgetOnTermination(NotificationHub $hub): void
    {
        foreach ($this->forgetsOnTermination as $listener) {
            $hub->forgetEvent($listener);
        }
    }
}
