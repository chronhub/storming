<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Notification\Cycle;

use Storm\Contract\Projector\Subscriptor;

final class CurrentCycle
{
    public function __invoke(Subscriptor $subscriptor): int
    {
        return $subscriptor->watcher()->cycle()->cycle();
    }
}
