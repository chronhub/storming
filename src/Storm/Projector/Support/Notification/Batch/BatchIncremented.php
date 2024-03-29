<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Notification\Batch;

use Storm\Contract\Projector\Subscriptor;

final class BatchIncremented
{
    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->watcher()->batch()->increment();

        $subscriptor->watcher()->masterCounter()->increment();
    }
}
