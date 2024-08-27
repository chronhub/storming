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

    public static function invalidGapPosition(string $streamName): self
    {
        return new self("Invalid gap position: no gap or checkpoints are outdated for stream $streamName");
    }

    public static function inconsistentGaps(string $streamName, int $lastCheckpointPosition): self
    {
        return new self("Inconsistent gaps: last checkpoint position $lastCheckpointPosition is not the max position of the previous gaps for stream $streamName");
    }

    public static function gapAlreadyRecorded(string $streamName, int $start, int $end): self
    {
        return new self("Gap at started position $start and ended position $end already recorded for stream $streamName");
    }
}
