<?php

declare(strict_types=1);

namespace Storm\Projector\Checkpoint;

use Storm\Projector\Workflow\Notification\RecoverableGapDetected;
use Storm\Projector\Workflow\Notification\UnrecoverableGapDetected;

/**
 * Each gap type has a corresponding notification class.
 *
 * @see GapDetector
 */
enum GapType: string
{
    /**
     * Stream position is a gap, but there is still retry left to fix the gap.
     */
    case RECOVERABLE_GAP = RecoverableGapDetected::class;

    /**
     * Stream position is a gap, and there is only one retry left.
     */
    case UNRECOVERABLE_GAP = UnrecoverableGapDetected::class;
}
