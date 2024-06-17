<?php

declare(strict_types=1);

namespace Storm\Projector\Checkpoint;

use DateTimeImmutable;

final readonly class StreamPoint
{
    public function __construct(
        public string $name,
        public int $position,
        public string|DateTimeImmutable $eventTime,
    ) {
    }
}
