<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Cycle;

use Storm\Contract\Projector\Subscriptor;

final class CycleIncremented
{
    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->watcher()->cycle->next();
    }
}
