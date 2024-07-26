<?php

declare(strict_types=1);

namespace Storm\Projector\Exception;

class CheckpointViolation extends RuntimeException
{
    public static function streamNotTracked(string $streamName): self
    {
        return new self("Checkpoint not tracked for stream $streamName");
    }

    public static function recordingGapDisabled(string $streamName): self
    {
        return new self("Recording gaps is disabled for stream $streamName and cannot be updated with gaps");
    }

    public static function invalidStreamPosition(string $streamName, int $streamPosition): self
    {
        return new self("Stream position $streamPosition must be greater than 0 for stream $streamName");
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
}
