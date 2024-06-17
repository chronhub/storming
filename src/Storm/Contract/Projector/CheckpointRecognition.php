<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use JsonSerializable;
use Storm\Projector\Checkpoint\Checkpoint;
use Storm\Projector\Checkpoint\StreamPoint;

/**
 * @template TCheckpoint of array{
 *     stream_name: string,
 *     position: int<0, max>,
 *     event_time: string|null,
 *     created_at: string,
 *     gaps: array<positive-int>|array,
 *     gap_type: string|null
 * }
 */
interface CheckpointRecognition extends JsonSerializable
{
    /**
     * Track event streams.
     */
    public function discover(string ...$streamNames): void;

    /**
     * Insert stream checkpoint.
     *
     * @return Checkpoint the checkpoint inserted with or without a gap
     */
    public function insert(StreamPoint $streamPoint): Checkpoint;

    /**
     * Update stream checkpoints.
     *
     * @param array<TCheckpoint> $checkpoints
     */
    public function update(array $checkpoints): void;

    /**
     * Returns the inserted stream checkpoints.
     *
     * @return array<string, Checkpoint>
     */
    public function toArray(): array;

    /**
     * Resets stream positions.
     */
    public function resets(): void;

    /**
     * Check if a gap is detected.
     */
    public function hasGap(): bool;

    /**
     * Sleep when a gap is detected.
     */
    public function sleepWhenGap(): void;

    /**
     * @return array<TCheckpoint>
     */
    public function jsonSerialize(): array;
}
