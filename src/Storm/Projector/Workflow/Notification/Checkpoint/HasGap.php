<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Checkpoint;

use Storm\Contract\Projector\Subscriptor;

final class HasGap
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        return $subscriptor->recognition()->hasGap();
    }
}
