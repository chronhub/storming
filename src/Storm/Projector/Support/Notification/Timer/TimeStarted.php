<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Notification\Timer;

use Storm\Contract\Projector\Subscriptor;

final class TimeStarted
{
    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->watcher()->time()->start();
    }
}
