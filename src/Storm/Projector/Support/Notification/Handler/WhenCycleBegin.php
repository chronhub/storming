<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Notification\Handler;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Support\Notification\Cycle\CycleBegan;
use Storm\Projector\Support\Notification\Cycle\CycleStarted;
use Storm\Projector\Support\Notification\Cycle\IsCycleStarted;
use Storm\Projector\Support\Notification\Timer\IsTimeStarted;
use Storm\Projector\Support\Notification\Timer\TimeStarted;

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
