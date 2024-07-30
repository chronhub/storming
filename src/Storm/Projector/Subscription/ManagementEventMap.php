<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Storm\Contract\Projector\EmitterManagement;
use Storm\Contract\Projector\Management;
use Storm\Contract\Projector\PersistentManagement;
use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Workflow\Notification\BeforeHandleStreamGap;
use Storm\Projector\Workflow\Notification\Management\PerformWhenThresholdIsReached;
use Storm\Projector\Workflow\Notification\Management\ProjectionClosed;
use Storm\Projector\Workflow\Notification\Management\ProjectionDiscarded;
use Storm\Projector\Workflow\Notification\Management\ProjectionFreed;
use Storm\Projector\Workflow\Notification\Management\ProjectionLockUpdated;
use Storm\Projector\Workflow\Notification\Management\ProjectionRestarted;
use Storm\Projector\Workflow\Notification\Management\ProjectionRevised;
use Storm\Projector\Workflow\Notification\Management\ProjectionRise;
use Storm\Projector\Workflow\Notification\Management\ProjectionStatusDisclosed;
use Storm\Projector\Workflow\Notification\Management\ProjectionStored;
use Storm\Projector\Workflow\Notification\Management\ProjectionSynchronized;
use Storm\Projector\Workflow\Notification\Management\StreamEventEmitted;
use Storm\Projector\Workflow\Notification\Management\StreamEventLinkedTo;
use Storm\Projector\Workflow\WorkflowContext;

final class ManagementEventMap
{
    public function subscribeTo(Management $management, WorkflowContext $workflowContext): void
    {
        $workflowContext->listenTo(
            PerformWhenThresholdIsReached::class,
            fn () => $management->performWhenThresholdIsReached(),
        );

        if ($management instanceof PersistentManagement) {
            $this->withListenerManagement($management, $workflowContext);
        }

        $this->withListeners($workflowContext);
    }

    private function withListenerManagement(PersistentManagement $management, WorkflowContext $workflowContext): void
    {
        $map = [
            ProjectionRise::class => fn () => $management->rise(),
            ProjectionLockUpdated::class => fn () => $management->shouldUpdateLock(),
            ProjectionStored::class => fn () => $management->store(),
            ProjectionClosed::class => fn () => $management->close(),
            ProjectionRevised::class => fn () => $management->revise(),
            ProjectionDiscarded::class => fn (WorkflowContext $workflowContext, ProjectionDiscarded $listener) => $management->discard($listener->withEmittedEvents),
            ProjectionFreed::class => fn () => $management->freed(),
            ProjectionRestarted::class => fn () => $management->restart(),
            ProjectionStatusDisclosed::class => fn () => $management->disclose(),
            ProjectionSynchronized::class => fn () => $management->synchronise(),
        ];

        if ($management instanceof EmitterManagement) {
            $map = $map + [
                StreamEventEmitted::class => fn (WorkflowContext $workflowContext, StreamEventEmitted $listener) => $management->emit($listener->event),
                StreamEventLinkedTo::class => fn (WorkflowContext $workflowContext, StreamEventLinkedTo $listener) => $management->linkTo($listener->streamName, $listener->event),
            ];
        }

        foreach ($map as $event => $callback) {
            $workflowContext->listenTo($event, $callback);
        }
    }

    private function withListeners(WorkflowContext $workflowContext): void
    {
        /**
         * @todo more info on checkpoint, we could keep the last checkpoint in memory
         *   to be retrieved when the gap is detected
         */
        $workflowContext->listenTo(BeforeHandleStreamGap::class, function () use ($workflowContext) {
            $currentGap = $workflowContext->recognition()->gapType();

            if ($currentGap instanceof GapType) {
                //dd($workflowContext->recognition()->toArray());
                $workflowContext->emit($currentGap->value);
            }
        });
    }
}
