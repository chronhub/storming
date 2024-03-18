<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Notification\Stream;

use Storm\Contract\Projector\Subscriptor;

final class NewEventStreamReset
{
    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->watcher()->streamDiscovery()->resetNewEventStreams();
    }
}
