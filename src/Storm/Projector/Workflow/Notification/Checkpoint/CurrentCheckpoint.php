<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Checkpoint;

use Storm\Contract\Projector\Subscriptor;
use Storm\Projector\Checkpoint\Checkpoint;

final class CurrentCheckpoint
{
    /**
     * @return array<string, Checkpoint>
     */
    public function __invoke(Subscriptor $subscriptor): array
    {
        return $subscriptor->recognition()->toArray();
    }
}
