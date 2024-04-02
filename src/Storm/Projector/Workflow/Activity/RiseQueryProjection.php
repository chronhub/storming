<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\Cycle\IsFirstCycle;
use Storm\Projector\Workflow\Notification\Stream\EventStreamDiscovered;

final readonly class RiseQueryProjection
{
    public function __invoke(NotificationHub $hub, callable $next): callable|bool
    {
        $hub->notifyWhen(
            $hub->expect(IsFirstCycle::class),
            fn (NotificationHub $hub) => $hub->notify(EventStreamDiscovered::class)
        );

        return $next($hub);
    }
}
