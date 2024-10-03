<?php

declare(strict_types=1);

namespace Storm\Projector\Projection;

use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Projection\Events\PerformWhenThresholdIsReached;
use Storm\Projector\Projection\Events\ProjectionClosed;
use Storm\Projector\Projection\Events\ProjectionDiscarded;
use Storm\Projector\Projection\Events\ProjectionFreed;
use Storm\Projector\Projection\Events\ProjectionLockUpdated;
use Storm\Projector\Projection\Events\ProjectionRestarted;
use Storm\Projector\Projection\Events\ProjectionRevised;
use Storm\Projector\Projection\Events\ProjectionRise;
use Storm\Projector\Projection\Events\ProjectionStatusDisclosed;
use Storm\Projector\Projection\Events\ProjectionStored;
use Storm\Projector\Projection\Events\ProjectionSynchronized;
use Storm\Projector\Projection\Events\StreamEventEmitted;
use Storm\Projector\Projection\Events\StreamEventLinkedTo;
use Storm\Projector\Workflow\Notification\BeforeHandleStreamGap;
use Storm\Projector\Workflow\Process;

final class ProviderEventMap
{
    public function subscribeTo(Projection $projection, Process $process): void
    {
        $process->addListener(BeforeHandleStreamGap::class, function (Process $process) {
            $currentGap = $process->recognition()->gapType();

            if ($currentGap instanceof GapType) {
                $process->dispatch($currentGap->value);
            }
        });

        $process->addListener(
            PerformWhenThresholdIsReached::class,
            fn () => $projection->performWhenThresholdIsReached(),
        );

        if ($projection instanceof PersistentProjection) {
            $this->withManagement($projection, $process);
        }
    }

    private function withManagement(PersistentProjection $management, Process $process): void
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

        if ($management instanceof EmitterProjection) {
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
