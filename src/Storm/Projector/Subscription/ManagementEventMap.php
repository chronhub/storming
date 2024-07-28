<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Storm\Contract\Projector\EmitterManagement;
use Storm\Contract\Projector\Management;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\PersistentManagement;
use Storm\Projector\Checkpoint\Checkpoint;
use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Workflow\Notification\Command\EventStreamDiscovered;
use Storm\Projector\Workflow\Notification\Command\NewEventStreamDiscovered;
use Storm\Projector\Workflow\Notification\Command\NoEventStreamDiscovered;
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
use Storm\Projector\Workflow\Notification\Promise\CurrentCheckpoint;
use Storm\Projector\Workflow\Notification\Promise\CurrentGapType;
use Storm\Projector\Workflow\Notification\Promise\CurrentNewEventStreams;
use Storm\Projector\Workflow\Notification\Promise\HasEventStreamDiscovered;
use Storm\Projector\Workflow\Notification\Promise\StreamEventProcessed;
use Storm\Projector\Workflow\Stage\BeforeHandleStreamGap;

final class ManagementEventMap
{
    public function subscribeTo(Management $management): void
    {
        $management->hub()->addEvents([
            PerformWhenThresholdIsReached::class => fn () => $management->performWhenThresholdIsReached(),
        ]);

        if ($management instanceof PersistentManagement) {
            $this->withListenerManagement($management);
        }

        $this->withListeners($management->hub());
    }

    private function withListenerManagement(PersistentManagement $management): void
    {
        $management->hub()->addEvents([
            ProjectionRise::class => fn () => $management->rise(),
            ProjectionLockUpdated::class => fn () => $management->shouldUpdateLock(),
            ProjectionStored::class => fn () => $management->store(),
            ProjectionClosed::class => fn () => $management->close(),
            ProjectionRevised::class => fn () => $management->revise(),
            ProjectionDiscarded::class => fn (NotificationHub $hub, ProjectionDiscarded $listener) => $management->discard($listener->withEmittedEvents),
            ProjectionFreed::class => fn () => $management->freed(),
            ProjectionRestarted::class => fn () => $management->restart(),
            ProjectionStatusDisclosed::class => fn () => $management->disclose(),
            ProjectionSynchronized::class => fn () => $management->synchronise(),
        ]);

        if ($management instanceof EmitterManagement) {
            $management->hub()->addEvents([
                StreamEventEmitted::class => fn (NotificationHub $hub, StreamEventEmitted $listener) => $management->emit($listener->event),
                StreamEventLinkedTo::class => fn (NotificationHub $hub, StreamEventLinkedTo $listener) => $management->linkTo($listener->streamName, $listener->event),
            ]);
        }
    }

    private function withListeners(NotificationHub $hub): void
    {
        /**
         * @todo more info on checkpoint, we could keep the last checkpoint in memory
         *   to be retrieved when the gap is detected
         */
        $hub->addEvent(BeforeHandleStreamGap::class, function (NotificationHub $hub) {
            $currentGap = $hub->await(CurrentGapType::class);

            if ($currentGap instanceof GapType) {
                //dd($hub->await(CurrentCheckpoint::class));
                $hub->emit($currentGap->value);
            }
        });

        $hub->addEvent(StreamEventProcessed::class, function (NotificationHub $hub, StreamEventProcessed $capture, Checkpoint $checkpoint) {
            // if gap it can only be a recoverable gap as gap handling is done after
        });

        $hub->addEvent(EventStreamDiscovered::class, function (NotificationHub $hub) {
            if (! $hub->await(HasEventStreamDiscovered::class)) {
                $hub->emit(NoEventStreamDiscovered::class);
            } else {
                $newEventStreams = $hub->await(CurrentNewEventStreams::class);

                foreach ($newEventStreams as $newEventStream) {
                    $hub->emit(NewEventStreamDiscovered::class, $newEventStream);
                }
            }
        });
    }
}
