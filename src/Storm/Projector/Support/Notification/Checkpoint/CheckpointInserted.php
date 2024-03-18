<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Notification\Checkpoint;

use DateTimeImmutable;
use Storm\Contract\Projector\Subscriptor;
use Storm\Projector\Checkpoint\Checkpoint;

final readonly class CheckpointInserted
{
    public function __construct(
        public string $streamName,
        public int $streamPosition,
        public string|DateTimeImmutable $eventTime,
    ) {
    }

    public function __invoke(Subscriptor $subscriptor): Checkpoint
    {
        return $subscriptor->recognition()->insert($this->streamName, $this->streamPosition, $this->eventTime);
    }
}
