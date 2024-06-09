<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\MasterCounter;

use Storm\Contract\Projector\Subscriptor;

final class CurrentMasterCount
{
    public function __invoke(Subscriptor $subscriptor): int
    {
        return $subscriptor->watcher()->masterCounter->current();
    }
}
