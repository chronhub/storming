<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\MasterCounter;

use Storm\Contract\Projector\Subscriptor;

final class MasterCounterReset
{
    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->watcher()->masterCounter()->reset();
    }
}
