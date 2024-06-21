<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\Stream\EventStreamDiscovered;

final readonly class RefreshProjection
{
    public function __construct(
        private RefreshRemoteStatus $remoteStatus,
        private bool $onlyOnceDiscovery
    ) {
    }

    public function __invoke(NotificationHub $hub, callable $next): callable|bool
    {
        // update the remote status
        $this->remoteStatus->refresh($hub);

        // watch again for event streams which may have changed
        // after the first discovery on rising projection
        if (! $this->onlyOnceDiscovery) {
            $hub->notify(EventStreamDiscovered::class);
        }

        return $next($hub);
    }
}
