<?php

declare(strict_types=1);

namespace Storm\Projector\Checkpoint;

use Storm\Contract\Projector\CheckpointRecognition;
use Storm\Projector\Exception\CheckpointViolation;

use function array_map;
use function array_merge;
use function in_array;

abstract class AbstractCheckpointRecognition implements CheckpointRecognition
{
    protected array $eventStreams = [];

    public function refreshStreams(array $eventStreams): void
    {
        $this->eventStreams = array_merge($this->eventStreams, $eventStreams);

        $this->checkpoints()->onDiscover(...$eventStreams);
    }

    public function toArray(): array
    {
        return $this->checkpoints()->all()->toArray();
    }

    public function jsonSerialize(): array
    {
        return array_map(fn (Checkpoint $checkpoint): array => $checkpoint->jsonSerialize(), $this->toArray());
    }

    /**
     * @throws CheckpointViolation when the stream name is not watched
     * @throws CheckpointViolation when the event position is less than 1
     * @throws CheckpointViolation when the event position is outdated
     */
    protected function getLastValidatedCheckpoint(string $streamName, int $eventPosition): Checkpoint
    {
        if (! in_array($streamName, $this->eventStreams, true)) {
            throw CheckpointViolation::streamNotDiscovered($streamName);
        }

        if ($eventPosition < 1) {
            throw CheckpointViolation::invalidEventPosition($streamName);
        }

        $lastCheckpoint = $this->checkpoints()->last($streamName);

        if ($eventPosition < $lastCheckpoint->position) {
            throw CheckpointViolation::outdatedEventPosition($streamName);
        }

        return $lastCheckpoint;
    }

    abstract protected function checkpoints(): CheckpointCollection;
}
