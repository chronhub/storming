<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use DateTimeImmutable;
use JsonSerializable;
use Storm\Projector\Checkpoint\Checkpoint;

/**
 * @template TCheckpoint of array{stream_name: string, position: int<0,max>, created_at: string, gaps: array<positive-int>|array}
 */
interface CheckpointRecognition extends JsonSerializable
{
    /**
     * Refresh event streams.
     */
    public function refreshStreams(array $eventStreams): void;

    /**
     * Insert stream checkpoint.
     *
     * @param  positive-int $streamPosition
     * @return Checkpoint   the last checkpoint inserted with or without a gap
     */
    public function insert(string $streamName, int $streamPosition, string|DateTimeImmutable $eventTime): Checkpoint;

    /**
     * Update stream checkpoints.
     *
     * @param array<TCheckpoint> $checkpoints
     */
    public function update(array $checkpoints): void;

    /**
     * Returns the last inserted stream checkpoints.
     *
     * @return array<string, Checkpoint>
     */
    public function checkpoints(): array;

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
