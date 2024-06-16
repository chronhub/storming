<?php

declare(strict_types=1);

namespace Storm\Projector\Checkpoint;

use Storm\Projector\Exception\CheckpointViolation;

use function array_intersect;
use function count;
use function reset;
use function sort;

class GapRules
{
    /**
     * @return $this
     *
     * @throws CheckpointViolation when the position is less than the previous position
     */
    public function mustBeGap(Checkpoint $checkpoint, int $position): self
    {
        if ($position - $checkpoint->position <= 0) {
            throw CheckpointViolation::invalidGapPosition($checkpoint->streamName);
        }

        return $this;
    }

    /**
     * @return $this
     *
     * @throws CheckpointViolation when the gap is already recorded
     */
    public function shouldNotAlreadyBeRecorded(Checkpoint $checkpoint, array $gaps): self
    {
        $duplicateGaps = count(array_intersect($gaps, $checkpoint->gaps));

        if ($duplicateGaps > 0) {
            throw CheckpointViolation::gapAlreadyRecorded($checkpoint->streamName, reset($gaps));
        }

        return $this;
    }

    /**
     * @return $this
     *
     * @throws CheckpointViolation when the gap is lower than the previous recorded gaps
     */
    public function mustBeGreaterThanPreviousGaps(Checkpoint $checkpoint, array $gaps): self
    {
        $previousGaps = $checkpoint->gaps;

        if ($previousGaps === []) {
            return $this;
        }

        sort($previousGaps);
        sort($gaps);

        if ($previousGaps[count($previousGaps) - 1] > $gaps[0]) {
            throw CheckpointViolation::gapLowerThanPrevious($checkpoint->streamName);
        }

        return $this;
    }
}
