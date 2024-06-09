<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Stream;

use Storm\Contract\Projector\Subscriptor;

final class HasEventStreamDiscovered
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        return $subscriptor->watcher()->streamDiscovery->hasEventStreams();
    }
}
