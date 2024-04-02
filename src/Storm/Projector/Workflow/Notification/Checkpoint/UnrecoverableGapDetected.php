<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Checkpoint;

final readonly class UnrecoverableGapDetected
{
    public function __construct(
        public string $streamName,
        public int $position
    ) {
    }
}
