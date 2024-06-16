<?php

declare(strict_types=1);

namespace Storm\Projector\Checkpoint;

use DateTimeImmutable;
use Storm\Projector\Exception\InvalidArgumentException;

// todo: remove this class
final class ReadOnlyCheckpointStore extends AbstractCheckpointRecognition
{
    public function __construct(private readonly CheckpointCollection $checkpoints)
    {
    }

    protected function checkpoints(): CheckpointCollection
    {
        return $this->checkpoints;
    }

    public function insert(string $streamName, int $streamPosition, string|DateTimeImmutable $eventTime): Checkpoint
    {
        $checkpoint = $this->getLastValidatedCheckpoint($streamName, $streamPosition);

        return $this->checkpoints->next($checkpoint, $streamPosition, $eventTime, null);
    }

    public function update(array $checkpoints): void
    {
        throw new InvalidArgumentException('Update checkpoint is not supported in memory.');
    }

    public function resets(): void
    {
        $this->checkpoints->flush();
    }

    public function hasGap(): bool
    {
        return false;
    }

    public function sleepWhenGap(): void
    {
    }
}
