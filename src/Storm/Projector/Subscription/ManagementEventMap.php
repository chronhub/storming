<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Storm\Contract\Projector\EmitterManagement;
use Storm\Contract\Projector\Management;
use Storm\Contract\Projector\PersistentManagement;
use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Workflow\Management\PerformWhenThresholdIsReached;
use Storm\Projector\Workflow\Management\ProjectionClosed;
use Storm\Projector\Workflow\Management\ProjectionDiscarded;
use Storm\Projector\Workflow\Management\ProjectionFreed;
use Storm\Projector\Workflow\Management\ProjectionLockUpdated;
use Storm\Projector\Workflow\Management\ProjectionRestarted;
use Storm\Projector\Workflow\Management\ProjectionRevised;
use Storm\Projector\Workflow\Management\ProjectionRise;
use Storm\Projector\Workflow\Management\ProjectionStatusDisclosed;
use Storm\Projector\Workflow\Management\ProjectionStored;
use Storm\Projector\Workflow\Management\ProjectionSynchronized;
use Storm\Projector\Workflow\Management\StreamEventEmitted;
use Storm\Projector\Workflow\Management\StreamEventLinkedTo;
use Storm\Projector\Workflow\Notification\BeforeHandleStreamGap;
use Storm\Projector\Workflow\Process;

final class ManagementEventMap
{
    public function subscribeTo(Management $management, Process $process): void
    {
        /**
         * @todo more info on checkpoint, we could keep the last checkpoint in memory
         *   to be retrieved when the gap is detected
         */
        $process->addListener(BeforeHandleStreamGap::class, function (Process $process) {
            $currentGap = $process->recognition()->gapType();

            if ($currentGap instanceof GapType) {
                //dd($workflowContext->recognition()->toArray());
                $process->dispatch($currentGap->value);
            }
        });

        $process->addListener(
            PerformWhenThresholdIsReached::class,
            fn () => $management->performWhenThresholdIsReached(),
        );

        if ($management instanceof PersistentManagement) {
            $this->withManagement($management, $process);
        }
    }

    private function withManagement(PersistentManagement $management, Process $process): void
    {
        $map = [
            ProjectionRise::class => fn () => $management->rise(),
            ProjectionLockUpdated::class => fn () => $management->shouldUpdateLock(),
            ProjectionStored::class => fn () => $management->store(),
            ProjectionClosed::class => fn () => $management->close(),
            ProjectionRevised::class => fn () => $management->revise(),
            ProjectionDiscarded::class => fn (Process $process, ProjectionDiscarded $listener) => $management->discard($listener->withEmittedEvents),
            ProjectionFreed::class => fn () => $management->freed(),
            ProjectionRestarted::class => fn () => $management->restart(),
            ProjectionStatusDisclosed::class => fn () => $management->disclose(),
            ProjectionSynchronized::class => fn () => $management->synchronise(),
        ];

        if ($management instanceof EmitterManagement) {
            $map = $map + [
                StreamEventEmitted::class => fn (Process $process, StreamEventEmitted $listener) => $management->emit($listener->event),
                StreamEventLinkedTo::class => fn (Process $process, StreamEventLinkedTo $listener) => $management->linkTo($listener->streamName, $listener->event),
            ];
        }

        foreach ($map as $event => $callback) {
            $process->addListener($event, $callback);
        }
    }
}
