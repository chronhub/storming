<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Cycle;

use Storm\Contract\Projector\Subscriptor;

final class IsFirstCycle
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        return $subscriptor->watcher()->cycle->isFirst();
    }
}
