<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\Command\EventStreamDiscovered;

final readonly class RefreshPersistentProjection
{
    use MonitorRemoteStatus;

    protected bool $onRise;

    public function __construct(private bool $onlyOnceDiscovery)
    {
        $this->onRise = false;
    }

    public function __invoke(NotificationHub $hub, callable $next): callable|bool
    {
        /**
         * Discover the remote status which may have changed during the projection
         */
        $this->discloseRemoteStatus($hub);

        /**
         * Discover event stream again which may have changed
         * after the first discovery on rising projection
         */
        if (! $this->onlyOnceDiscovery) {
            $hub->emit(EventStreamDiscovered::class);
        }

        return $next($hub);
    }
}
