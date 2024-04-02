<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\Batch\BatchSleep;

final readonly class SleepForQuery
{
    public function __invoke(NotificationHub $hub, callable $next): callable|bool
    {
        // checkMe
        $hub->notify(BatchSleep::class);

        return $next($hub);
    }
}
