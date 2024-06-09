<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Timer;

use Storm\Contract\Projector\Subscriptor;

final class TimeReset
{
    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->watcher()->time->reset();
    }
}
