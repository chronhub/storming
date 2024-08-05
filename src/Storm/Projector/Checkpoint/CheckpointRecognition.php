<?php

declare(strict_types=1);

namespace Storm\Projector\Checkpoint;

use JsonSerializable;

/**
 * @phpstan-import-type CheckpointArray from Checkpoint
 */
interface CheckpointRecognition extends JsonSerializable
{
    /**
     * Track event streams.
     */
    public function track(string ...$streamNames): void;

    /**
     * Record stream checkpoint when a gap is detected and unrecoverable.
     * Otherwise, return checkpoint.
     */
    public function record(StreamPoint $streamPoint): Checkpoint;

    /**
     * Update stream checkpoints.
     *
     * @param array<CheckpointArray> $checkpoints
     */
    public function update(array $checkpoints): void;

    /**
     * Resets stream positions.
     */
    public function resets(): void;

    /**
     * Check if a gap is detected.
     */
    public function hasGap(): bool;

    /**
     * Get the current gap type if a gap was detected.
     */
    public function gapType(): ?GapType;

    /**
     * Sleep when a gap is detected.
     */
    public function sleepOnGap(): void;

    /**
     * Returns stream checkpoints as an array.
     *
     * @return array<string, Checkpoint>
     */
    public function toArray(): array;

    /**
     * Returns stream checkpoints as an array of scalar values.
     *
     * @return array<CheckpointArray>
     */
    public function jsonSerialize(): array;
}
