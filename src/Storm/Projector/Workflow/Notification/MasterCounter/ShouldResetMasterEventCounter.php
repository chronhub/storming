<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\MasterCounter;

use Storm\Contract\Projector\Subscriptor;

final class ShouldResetMasterEventCounter
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        return $subscriptor->watcher()->masterCounter->isDoNotReset();
    }
}
