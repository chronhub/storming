<?php

declare(strict_types=1);

namespace Storm\Projector\Checkpoint;

use Storm\Projector\Exception\CheckpointViolation;

use function in_array;
use function max;
use function min;

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
        foreach ($gaps as $gap) {
            if (in_array($gap, $checkpoint->gaps, true)) {
                throw CheckpointViolation::gapAlreadyRecorded($checkpoint->streamName, $gap);
            }
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
        if ($checkpoint->gaps !== [] && (max($checkpoint->gaps) > min($gaps))) {
            throw CheckpointViolation::gapLowerThanPrevious($checkpoint->streamName);
        }

        return $this;
    }
}
