<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\Cycle\IsFirstCycle;
use Storm\Projector\Workflow\Notification\Management\ProjectionRise;

final readonly class RisePersistentProjection
{
    public function __construct(
        private DiscoverRemoteStatus $discoverRemoteStatus
    ) {
    }

    public function __invoke(NotificationHub $hub, callable $next): callable|bool
    {
        if ($hub->expect(IsFirstCycle::class)) {
            if ($this->discoverRemoteStatus->onlyOnce($hub)) {
                return false;
            }

            $hub->trigger(new ProjectionRise());
        }

        return $next($hub);
    }
}
