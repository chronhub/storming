<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Handler;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\Command\EventStreamDiscovered;
use Storm\Projector\Workflow\Notification\Command\NewEventStreamDiscovered;
use Storm\Projector\Workflow\Notification\Command\NoEventStreamDiscovered;
use Storm\Projector\Workflow\Notification\Promise\CurrentNewEventStreams;
use Storm\Projector\Workflow\Notification\Promise\HasEventStreamDiscovered;

/**
 * CheckMe not used anywhere
 */
final class WhenEventStreamDiscovered
{
    public function __invoke(NotificationHub $hub, EventStreamDiscovered $capture): void
    {
        if (! $hub->await(HasEventStreamDiscovered::class)) {
            $hub->emit(NoEventStreamDiscovered::class);
        } else {
            $newEventStreams = $hub->await(CurrentNewEventStreams::class);

            foreach ($newEventStreams as $newEventStream) {
                $hub->emit(NewEventStreamDiscovered::class, $newEventStream);
            }
        }
    }
}
