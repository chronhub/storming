<?php

declare(strict_types=1);

namespace Storm\Projector\Checkpoint;

use Storm\Projector\Exception\CheckpointViolation;
use Storm\Stream\StreamPosition;

use function array_intersect;
use function array_merge;
use function max;
use function min;
use function range;

class GapRules
{
    /**
     * Merge the gaps between the last checkpoint position and the current position.
     *
     * @return array<positive-int>
     *
     * @throws CheckpointViolation when the gap is less than or equal to zero
     * @throws CheckpointViolation when the gap is less than the last recorded position
     * @throws CheckpointViolation when one of the gap ranges are already recorded
     * @throws CheckpointViolation when one of the gap ranges are lower than the last recorded gaps
     */
    public function mergeGaps(Checkpoint $lastCheckpoint, StreamPosition $streamPosition): array
    {
        $this->validateGap($lastCheckpoint, $streamPosition);

        $gaps = $this->calculateGaps($lastCheckpoint->position, $streamPosition->value);

        $this->validateGapsNotRecorded($lastCheckpoint, $gaps, $streamPosition->value);
        $this->validateGapsGreaterThanPrevious($lastCheckpoint, $gaps);

        return array_merge($lastCheckpoint->gaps, $gaps);
    }

    /**
     * Check if the gap is greater than the last recorded position.
     *
     * @throws CheckpointViolation
     */
    protected function validateGap(Checkpoint $checkpoint, StreamPosition $streamPosition): void
    {
        if ($streamPosition->isLessThanOrEqual($checkpoint->position)) {
            throw CheckpointViolation::invalidGapPosition($checkpoint->streamName);
        }
    }

    /**
     * Calculate the gaps between the last recorded position and the current position.
     *
     * Positions are adjusted to exclude the last recorded position.
     *
     * @return array<positive-int>
     */
    protected function calculateGaps(int $lastPosition, int $currentGap): array
    {
        return range($lastPosition + 1, $currentGap - 1);
    }

    /**
     * Check if the gaps are not already recorded.
     *
     * @param array<positive-int> $gaps
     *
     * @throws CheckpointViolation
     */
    protected function validateGapsNotRecorded(Checkpoint $checkpoint, array $gaps, int $streamPosition): void
    {
        if (array_intersect($gaps, $checkpoint->gaps) !== []) {
            throw CheckpointViolation::gapAlreadyRecorded($checkpoint->streamName, $streamPosition);
        }
    }

    /**
     * Check if the gaps are greater than the last recorded gaps.
     *
     * @param array<positive-int> $gaps
     *
     * @throws CheckpointViolation
     */
    protected function validateGapsGreaterThanPrevious(Checkpoint $checkpoint, array $gaps): void
    {
        if ($checkpoint->gaps === []) {
            return;
        }

        if (max($checkpoint->gaps) > min($gaps)) {
            throw CheckpointViolation::gapLowerThanPrevious($checkpoint->streamName);
        }
    }
}
