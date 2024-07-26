<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\Command\EventStreamDiscovered;

final readonly class RefreshQueryProjection
{
    public function __construct(private bool $onlyOnceDiscovery) {}

    public function __invoke(NotificationHub $hub, callable $next): callable|bool
    {
        /**
         * Watch again for event streams which may have changed
         * after the first discovery on rising projection
         */
        if (! $this->onlyOnceDiscovery) {
            $hub->emit(EventStreamDiscovered::class);
        }

        return $next($hub);
    }
}
