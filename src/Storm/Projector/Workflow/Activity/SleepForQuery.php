<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\Command\BatchStreamSleep;

final readonly class SleepForQuery
{
    public function __invoke(NotificationHub $hub, callable $next): callable|bool
    {
        $hub->emit(BatchStreamSleep::class);

        return $next($hub);
    }
}
