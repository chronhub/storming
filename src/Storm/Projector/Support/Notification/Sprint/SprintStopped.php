<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Notification\Sprint;

use Storm\Contract\Projector\Subscriptor;

final readonly class SprintStopped
{
    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->watcher()->sprint()->halt();
    }
}
