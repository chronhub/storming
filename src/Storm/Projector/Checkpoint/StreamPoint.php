<?php

declare(strict_types=1);

namespace Storm\Projector\Checkpoint;

use DateTimeImmutable;
use Storm\Projector\Exception\CheckpointViolation;

final readonly class StreamPoint
{
    /**
     * @param positive-int $position
     *
     * @throws CheckpointViolation when the position is less than one
     */
    public function __construct(
        public string $name,
        public int $position,
        public string|DateTimeImmutable $eventTime,
    ) {
        // @phpstan-ignore-next-line
        if ($this->position < 1) {
            throw CheckpointViolation::invalidStreamPosition($this->name, $this->position);
        }
    }
}
