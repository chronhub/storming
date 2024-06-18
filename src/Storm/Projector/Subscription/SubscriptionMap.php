<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Storm\Contract\Projector\Management;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\PersistentManagement;
use Storm\Projector\Workflow\Notification\Checkpoint\CheckpointInserted;
use Storm\Projector\Workflow\Notification\Cycle\CycleBegan;
use Storm\Projector\Workflow\Notification\Cycle\CycleRenewed;
use Storm\Projector\Workflow\Notification\Handler\WhenBatchLoaded;
use Storm\Projector\Workflow\Notification\Handler\WhenCheckpointInserted;
use Storm\Projector\Workflow\Notification\Handler\WhenCycleBegin;
use Storm\Projector\Workflow\Notification\Handler\WhenCycleRenewed;
use Storm\Projector\Workflow\Notification\Handler\WhenEventStreamDiscovered;
use Storm\Projector\Workflow\Notification\Management\EventEmitted;
use Storm\Projector\Workflow\Notification\Management\EventLinkedTo;
use Storm\Projector\Workflow\Notification\Management\ProjectionClosed;
use Storm\Projector\Workflow\Notification\Management\ProjectionDiscarded;
use Storm\Projector\Workflow\Notification\Management\ProjectionFreed;
use Storm\Projector\Workflow\Notification\Management\ProjectionLockUpdated;
use Storm\Projector\Workflow\Notification\Management\ProjectionPersistedWhenThresholdIsReached;
use Storm\Projector\Workflow\Notification\Management\ProjectionRestarted;
use Storm\Projector\Workflow\Notification\Management\ProjectionRevised;
use Storm\Projector\Workflow\Notification\Management\ProjectionRise;
use Storm\Projector\Workflow\Notification\Management\ProjectionStatusDisclosed;
use Storm\Projector\Workflow\Notification\Management\ProjectionStored;
use Storm\Projector\Workflow\Notification\Management\ProjectionSynchronized;
use Storm\Projector\Workflow\Notification\Management\SnapshotCheckpointCaptured;
use Storm\Projector\Workflow\Notification\Stream\EventStreamDiscovered;
use Storm\Projector\Workflow\Notification\Stream\StreamIteratorSet;

final class SubscriptionMap
{
    public function subscribeTo(Management $management): void
    {
        if ($management instanceof PersistentManagement) {
            $this->withHooks($management);
        }

        $this->withListeners($management->hub());
    }

    private function withHooks(PersistentManagement $management): void
    {
        $management->hub()->addHooks([
            ProjectionRise::class => fn () => $management->rise(),
            ProjectionLockUpdated::class => fn () => $management->shouldUpdateLock(),
            ProjectionStored::class => fn () => $management->store(),
            ProjectionPersistedWhenThresholdIsReached::class => fn () => $management->persistWhenThresholdIsReached(),
            ProjectionClosed::class => fn () => $management->close(),
            ProjectionRevised::class => fn () => $management->revise(),
            ProjectionDiscarded::class => fn (ProjectionDiscarded $listener) => $management->discard($listener->withEmittedEvents),
            ProjectionFreed::class => fn () => $management->freed(),
            ProjectionRestarted::class => fn () => $management->restart(),
            ProjectionStatusDisclosed::class => fn () => $management->disclose(),
            ProjectionSynchronized::class => fn () => $management->synchronise(),
            SnapshotCheckpointCaptured::class => fn (SnapshotCheckpointCaptured $listener) => $management->snapshot($listener->checkpoint),
        ]);

        if ($management instanceof EmittingManagement) {
            $management->hub()->addHooks([
                EventEmitted::class => fn (EventEmitted $listener) => $management->emit($listener->event),
                EventLinkedTo::class => fn (EventLinkedTo $listener) => $management->linkTo($listener->streamName, $listener->event),
            ]);
        }
    }

    private function withListeners(NotificationHub $hub): void
    {
        $hub->addListeners([
            CycleBegan::class => WhenCycleBegin::class,
            CycleRenewed::class => WhenCycleRenewed::class,
            StreamIteratorSet::class => WhenBatchLoaded::class,
            CheckpointInserted::class => WhenCheckpointInserted::class,
            EventStreamDiscovered::class => WhenEventStreamDiscovered::class,
        ]);
    }
}
