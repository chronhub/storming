<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Notification\Stream;

use Storm\Contract\Projector\Subscriptor;

final class GetNewEventStreams
{
    public function __invoke(Subscriptor $subscriptor): array
    {
        return $subscriptor->watcher()->streamDiscovery()->newEventStreams();
    }
}
