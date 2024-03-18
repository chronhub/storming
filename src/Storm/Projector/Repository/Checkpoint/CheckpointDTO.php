<?php

declare(strict_types=1);

namespace Storm\Projector\Repository\Checkpoint;

final readonly class CheckpointDTO
{
    public function __construct(
        public string $projectionName,
        public string $streamName,
        public int $position,
        public string $eventTime,
        public string $createdAt,
        public string $gaps
    ) {
    }
}
