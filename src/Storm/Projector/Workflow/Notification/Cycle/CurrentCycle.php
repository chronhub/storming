<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Cycle;

use Storm\Contract\Projector\Subscriptor;

final class CurrentCycle
{
    public function __invoke(Subscriptor $subscriptor): int
    {
        return $subscriptor->watcher()->cycle->current();
    }
}
