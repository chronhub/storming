<?php

declare(strict_types=1);

namespace Storm\Projector\Checkpoint;

class CheckpointFactory
{
    public static function from(
        string $streamName,
        int $position,
        ?string $eventTime,
        string $createdAt,
        array $gaps,
        ?GapType $gapType
    ): Checkpoint {
        return new Checkpoint($streamName, $position, $eventTime, $createdAt, $gaps, $gapType);
    }

    /**
     * @param array{
     *     stream_name: string,
     *     position: int<0, max>,
     *     event_time: string|null,
     *     created_at: string,
     *     gaps: array<positive-int>|array,
     *     gap_type: string|null
     * } $checkpoint
     */
    public static function fromArray(array $checkpoint): Checkpoint
    {
        $gapType = $checkpoint['gap_type'] !== null ? GapType::from($checkpoint['gap_type']) : null;

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
