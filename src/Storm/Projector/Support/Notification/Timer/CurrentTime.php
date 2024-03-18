<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Notification\Timer;

use Storm\Contract\Projector\Subscriptor;

final class CurrentTime
{
    public function __invoke(Subscriptor $subscriptor): int
    {
        return $subscriptor->watcher()->time()->getCurrentTime();
    }
}
