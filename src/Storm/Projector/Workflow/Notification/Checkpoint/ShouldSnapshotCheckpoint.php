<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Checkpoint;

use Storm\Projector\Checkpoint\Checkpoint;

final readonly class ShouldSnapshotCheckpoint
{
    public function __construct(public Checkpoint $checkpoint)
    {
    }
}
