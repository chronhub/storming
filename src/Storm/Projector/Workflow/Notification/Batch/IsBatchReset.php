<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Batch;

use Storm\Contract\Projector\Subscriptor;

final class IsBatchReset
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        return $subscriptor->watcher()->batch()->isReset();
    }
}
