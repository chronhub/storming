<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Checkpoint;

final class RecoverableGapDetected
{
    public function __construct(
        public string $streamName,
        public int $position
    ) {}
}
