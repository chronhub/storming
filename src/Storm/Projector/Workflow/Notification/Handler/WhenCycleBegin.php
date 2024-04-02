<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Handler;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\Cycle\CycleBegan;
use Storm\Projector\Workflow\Notification\Cycle\CycleStarted;
use Storm\Projector\Workflow\Notification\Cycle\IsCycleStarted;
use Storm\Projector\Workflow\Notification\Timer\IsTimeStarted;
use Storm\Projector\Workflow\Notification\Timer\TimeStarted;

final class WhenCycleBegin
{
    public function __invoke(NotificationHub $hub, CycleBegan $event): void
    {
        $hub->notifyWhen(
            ! $hub->expect(IsCycleStarted::class),
            fn () => $hub->notify(CycleStarted::class)
        )->notifyWhen(
            ! $hub->expect(IsTimeStarted::class),
            fn () => $hub->notify(TimeStarted::class)
        );
    }
}
