<?php

declare(strict_types=1);

namespace Storm\Projector\Checkpoint;

use Storm\Projector\Workflow\Notification\Checkpoint\GapDetected;
use Storm\Projector\Workflow\Notification\Checkpoint\RecoverableGapDetected;
use Storm\Projector\Workflow\Notification\Checkpoint\UnrecoverableGapDetected;

enum GapType: string
{
    /**
     * Gap detected and filled
     */
    case IN_GAP = GapDetected::class;

    /**
     * Stream position is a gap but there is still retry left to recover
     */
    case RECOVERABLE_GAP = RecoverableGapDetected::class;

    /**
     * Stream position is a gap, and there is no retry left to recover,
     * but the projection can be terminated
     */
    case UNRECOVERABLE_GAP = UnrecoverableGapDetected::class;
}
