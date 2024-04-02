<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\Cycle\IsFirstCycle;
use Storm\Projector\Workflow\Notification\Management\ProjectionRise;

final class RisePersistentProjection
{
    use MonitorRemoteStatus;

    public function __invoke(NotificationHub $hub, callable $next): callable|bool
    {
        if ($hub->expect(IsFirstCycle::class)) {
            if ($this->shouldStop($hub)) {
                return false;
            }

            $hub->trigger(new ProjectionRise());
        }

        return $next($hub);
    }
}
