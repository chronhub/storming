<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Sprint;

use Storm\Contract\Projector\Subscriptor;

final class IsSprintRunning
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        return $subscriptor->watcher()->sprint()->inProgress();
    }
}
