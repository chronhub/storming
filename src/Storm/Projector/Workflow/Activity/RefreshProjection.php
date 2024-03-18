<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Support\Notification\Stream\EventStreamDiscovered;

final class RefreshProjection
{
    use MonitorRemoteStatus;

    public function __construct(private readonly bool $onlyOnceDiscovery)
    {
    }

    public function __invoke(NotificationHub $hub, callable $next): callable|bool
    {
        // monitor update in the remote status
        $this->refreshStatus($hub);

        // watch again for event streams which may have changed
        // after the first discovery on rising projection
        if (! $this->onlyOnceDiscovery) {
            $hub->notify(EventStreamDiscovered::class);
        }

        return $next($hub);
    }
}
