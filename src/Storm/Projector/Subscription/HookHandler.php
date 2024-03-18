<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\PersistentManagement;
use Storm\Projector\Support\Notification\Management\EventEmitted;
use Storm\Projector\Support\Notification\Management\EventLinkedTo;
use Storm\Projector\Support\Notification\Management\ProjectionClosed;
use Storm\Projector\Support\Notification\Management\ProjectionDiscarded;
use Storm\Projector\Support\Notification\Management\ProjectionFreed;
use Storm\Projector\Support\Notification\Management\ProjectionLockUpdated;
use Storm\Projector\Support\Notification\Management\ProjectionPersistedWhenThresholdIsReached;
use Storm\Projector\Support\Notification\Management\ProjectionRestarted;
use Storm\Projector\Support\Notification\Management\ProjectionRevised;
use Storm\Projector\Support\Notification\Management\ProjectionRise;
use Storm\Projector\Support\Notification\Management\ProjectionStatusDisclosed;
use Storm\Projector\Support\Notification\Management\ProjectionStored;
use Storm\Projector\Support\Notification\Management\ProjectionSynchronized;
use Storm\Projector\Support\Notification\Management\SnapshotCheckpointCaptured;

final class HookHandler
{
    public static function subscribe(NotificationHub $task, PersistentManagement $management): void
    {
        $task->addHooks([
            ProjectionRise::class => fn () => $management->rise(),
            ProjectionLockUpdated::class => fn () => $management->tryUpdateLock(),
            ProjectionStored::class => fn () => $management->store(),
            ProjectionPersistedWhenThresholdIsReached::class => fn () => $management->persistWhenThresholdIsReached(),
            ProjectionClosed::class => fn () => $management->close(),
            ProjectionRevised::class => fn () => $management->revise(),
            ProjectionDiscarded::class => fn ($listener) => $management->discard($listener->withEmittedEvents),
            ProjectionFreed::class => fn () => $management->freed(),
            ProjectionRestarted::class => fn () => $management->restart(),
            ProjectionStatusDisclosed::class => fn () => $management->disclose(),
            ProjectionSynchronized::class => fn () => $management->synchronise(),
            SnapshotCheckpointCaptured::class => fn ($listener) => $management->snapshot($listener->checkpoint),
        ]);

        if ($management instanceof EmittingManagement) {
            $task->addHooks([
                EventEmitted::class => fn ($listener) => $management->emit($listener->event),
                EventLinkedTo::class => fn ($listener) => $management->linkTo($listener->streamName, $listener->event),
            ]);
        }
    }
}
