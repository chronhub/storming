<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Notification\Checkpoint;

use Storm\Contract\Projector\Subscriptor;

final readonly class CheckpointReset
{
    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->recognition()->resets();
    }
}
