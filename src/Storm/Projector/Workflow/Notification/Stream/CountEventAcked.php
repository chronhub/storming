<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Stream;

use Storm\Contract\Projector\Subscriptor;

final class CountEventAcked
{
    public function __invoke(Subscriptor $subscriptor): int
    {
        return $subscriptor->watcher()->ackedEvent->count();
    }
}
