<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Timer;

use Storm\Contract\Projector\Subscriptor;

final class GetElapsedTime
{
    public function __invoke(Subscriptor $subscriptor): int
    {
        return $subscriptor->watcher()->time()->getElapsedTime();
    }
}
