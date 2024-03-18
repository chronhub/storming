<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Support\Notification\Cycle\CycleBegan;
use Storm\Projector\Support\Notification\Cycle\CycleRenewed;
use Storm\Projector\Support\Notification\Sprint\IsSprintTerminated;

final class CycleObserver
{
    public function __invoke(NotificationHub $hub, callable $next): bool
    {
        $hub->notify(CycleBegan::class);

        $next($hub);

        return $this->onCycleRenewed($hub);
    }

    private function onCycleRenewed(NotificationHub $hub): bool
    {
        $hub->notify(CycleRenewed::class);

        return ! $hub->expect(IsSprintTerminated::class);
    }
}
