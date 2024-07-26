<?php

declare(strict_types=1);

namespace Storm\Projector\Checkpoint;

use Storm\Clock\PointInTime;

/**
 * @phpstan-import-type CheckpointArray from Checkpoint
 */
class CheckpointFactory
{
    /**
     * Return a new checkpoint with a stream name and created at.
     * The position is set to zero.
     */
    public static function new(string $streamName, string $createdAt): Checkpoint
    {
        return new Checkpoint($streamName, 0, null, $createdAt, [], null);
    }

    /**
     * Return a new checkpoint from given parameters.
     */
    public static function from(
        string $streamName,
        int $position,
        ?string $eventTime,
        string $createdAt,
        array $gaps,
        ?GapType $gapType
    ): Checkpoint {
        return new Checkpoint(
            $streamName, $position, $eventTime, $createdAt, $gaps, $gapType
        );
    }

    /**
     * Return a new checkpoint from a stream point.
     */
    public static function fromStreamPoint(
        StreamPoint $streamPoint,
        string $createdAt,
        array $gaps,
        ?GapType $gapType
    ): Checkpoint {
        $eventTime = $streamPoint->eventTime instanceof PointInTime
            ? $streamPoint->eventTime->format()
            : $streamPoint->eventTime;

        return new Checkpoint(
            $streamPoint->name, $streamPoint->position->value, $eventTime,
            $createdAt, $gaps, $gapType
        );
    }

    /**
     * Return a new checkpoint without gaps and gap type.
     */
    public static function noGap(Checkpoint $checkpoint): Checkpoint
    {
        return new Checkpoint(
            $checkpoint->streamName,
            $checkpoint->position,
            $checkpoint->eventTime,
            $checkpoint->createdAt,
            [],
            null
        );
    }

    /**
     * Return a new checkpoint from an array.
     * All values need to be scalars.
     *
     * @param CheckpointArray $checkpoint
     */
    public static function fromArray(array $checkpoint): Checkpoint
    {
        $gapType = $checkpoint['gap_type'] !== null
            ? GapType::from($checkpoint['gap_type'])
            : null;

        return new Checkpoint(
            $checkpoint['stream_name'],
            $checkpoint['position'],
            $checkpoint['event_time'],
            $checkpoint['created_at'],
            $checkpoint['gaps'],
            $gapType
        );
    }
}
