<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Timer;

use Storm\Contract\Projector\Subscriptor;

final class IsTimeStarted
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        return $subscriptor->watcher()->time->isStarted();
    }
}
