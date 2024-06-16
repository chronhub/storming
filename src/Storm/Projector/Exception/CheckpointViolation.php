<?php

declare(strict_types=1);

namespace Storm\Projector\Exception;

class CheckpointViolation extends RuntimeException
{
    public static function streamNotDiscovered(string $streamName): self
    {
        return new self("Stream $streamName is not watched");
    }

    public static function invalidEventPosition(string $streamName): self
    {
        return new self("Stream event position must be greater than 0 for stream $streamName");
    }

    public static function invalidEventTime(string $streamName): self
    {
        return new self("Stream event time must be a valid date when position is not zero for stream $streamName");
    }

    public static function outdatedEventPosition(string $streamName): self
    {
        return new self("Position given for stream $streamName is outdated");
    }

    public static function invalidGapPosition(string $streamName): self
    {
        return new self("Invalid gap position: no gap or checkpoints are outdated for stream $streamName");
    }

    public static function gapAlreadyRecorded(string $streamName, int $gap): self
    {
        return new self("Gap at position $gap already recorded for stream $streamName");
    }

    public static function gapLowerThanPrevious(string $streamName): self
    {
        return new self("Cannot record gaps which are lower than previous recorded gaps for stream $streamName");
    }

    public static function checkpointNotFound(string $streamName): self
    {
        return new self("Checkpoint not found for stream $streamName");
    }
}
