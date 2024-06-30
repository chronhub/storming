<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\Cycle\BeforeCycleRenewed;
use Storm\Projector\Workflow\Notification\Cycle\CycleBegan;
use Storm\Projector\Workflow\Notification\Cycle\CycleRenewed;
use Storm\Projector\Workflow\Notification\Sprint\IsSprintTerminated;

final class CycleObserver
{
    public function __invoke(NotificationHub $hub, callable $next): bool
    {
        $hub->notify(CycleBegan::class);

        $next($hub);

        // alert before handler is called which can reset some watcher state
        $hub->notify(BeforeCycleRenewed::class);

        $hub->notify(CycleRenewed::class);

        return ! $hub->expect(IsSprintTerminated::class);
    }
}
