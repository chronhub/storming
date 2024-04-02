<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Batch;

use Storm\Contract\Projector\Subscriptor;

final class BatchSleep
{
    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->watcher()->batchStream()->sleep();
    }
}
