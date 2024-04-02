<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Checkpoint;

use Storm\Contract\Projector\Subscriptor;

final readonly class CheckpointUpdated
{
    public function __construct(public array $checkpoints)
    {
    }

    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->recognition()->update($this->checkpoints);
    }
}
