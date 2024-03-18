<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Notification\Batch;

use Storm\Contract\Projector\Subscriptor;

final class IsBatchReached
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        return $subscriptor->watcher()->batch()->isReached();
    }
}
