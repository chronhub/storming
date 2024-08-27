<?php

declare(strict_types=1);

namespace Storm\Projector\Provider;

use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Provider\Events\PerformWhenThresholdIsReached;
use Storm\Projector\Provider\Events\ProjectionClosed;
use Storm\Projector\Provider\Events\ProjectionDiscarded;
use Storm\Projector\Provider\Events\ProjectionFreed;
use Storm\Projector\Provider\Events\ProjectionLockUpdated;
use Storm\Projector\Provider\Events\ProjectionRestarted;
use Storm\Projector\Provider\Events\ProjectionRevised;
use Storm\Projector\Provider\Events\ProjectionRise;
use Storm\Projector\Provider\Events\ProjectionStatusDisclosed;
use Storm\Projector\Provider\Events\ProjectionStored;
use Storm\Projector\Provider\Events\ProjectionSynchronized;
use Storm\Projector\Provider\Events\StreamEventEmitted;
use Storm\Projector\Provider\Events\StreamEventLinkedTo;
use Storm\Projector\Workflow\Notification\BeforeHandleStreamGap;
use Storm\Projector\Workflow\Process;

final class ProviderEventMap
{
    public function subscribeTo(Provider $management, Process $process): void
    {
        $process->addListener(BeforeHandleStreamGap::class, function (Process $process) {
            $currentGap = $process->recognition()->gapType();

            if ($currentGap instanceof GapType) {
                $process->dispatch($currentGap->value);
            }
        });

        $process->addListener(
            PerformWhenThresholdIsReached::class,
            fn () => $management->performWhenThresholdIsReached(),
        );

        if ($management instanceof PersistentProvider) {
            $this->withManagement($management, $process);
        }
    }

    private function withManagement(PersistentProvider $management, Process $process): void
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

        if ($management instanceof EmitterProvider) {
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
