<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Notification\Checkpoint;

use Storm\Contract\Projector\Subscriptor;

final class SleepOnGap
{
    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->recognition()->sleepWhenGap();
    }
}
