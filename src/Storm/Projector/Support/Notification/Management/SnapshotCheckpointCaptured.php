<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Notification\Management;

use Storm\Projector\Checkpoint\Checkpoint;

final readonly class SnapshotCheckpointCaptured
{
    public function __construct(public Checkpoint $checkpoint)
    {
    }
}
