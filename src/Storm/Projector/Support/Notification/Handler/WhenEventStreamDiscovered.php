<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Notification\Handler;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Support\Notification\Stream\EventStreamDiscovered;
use Storm\Projector\Support\Notification\Stream\GetNewEventStreams;
use Storm\Projector\Support\Notification\Stream\HasEventStreamDiscovered;
use Storm\Projector\Support\Notification\Stream\NewEventStreamDiscovered;
use Storm\Projector\Support\Notification\Stream\NoEventStreamDiscovered;

final class WhenEventStreamDiscovered
{
    public function __invoke(NotificationHub $hub, EventStreamDiscovered $capture): void
    {
        if (! $hub->expect(HasEventStreamDiscovered::class)) {
            $hub->notify(NoEventStreamDiscovered::class);
        } else {
            $newEventStreams = $hub->expect(GetNewEventStreams::class);

            foreach ($newEventStreams as $newEventStream) {
                $hub->notify(NewEventStreamDiscovered::class, $newEventStream);
            }
        }
    }
}
