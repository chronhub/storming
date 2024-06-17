<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Checkpoint;

use DateTimeImmutable;
use Storm\Contract\Projector\Subscriptor;
use Storm\Projector\Checkpoint\Checkpoint;
use Storm\Projector\Checkpoint\StreamPoint;

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
        $streamPoint = new StreamPoint($this->streamName, $this->streamPosition, $this->eventTime);

        return $subscriptor->recognition()->insert($streamPoint);
    }
}
