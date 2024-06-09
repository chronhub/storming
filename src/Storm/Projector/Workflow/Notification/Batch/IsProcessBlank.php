<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Batch;

use Storm\Contract\Projector\Subscriptor;

final class IsProcessBlank
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        return $subscriptor->watcher()->batchCounter->isReset() &&
            ! $subscriptor->watcher()->ackedStream->hasStreams();
    }
}
