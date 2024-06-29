<?php

declare(strict_types=1);

namespace Storm\Projector\Checkpoint;

use LogicException;
use Storm\Contract\Projector\GapRecognition;

/**
 * @deprecated
 */
final class NoopGapDetector implements GapRecognition
{
    public function isRecoverable(): bool
    {
        return false;
    }

    public function hasGap(): bool
    {
        return false;
    }

    public function hasRetry(): bool
    {
        return false;
    }

    public function retryLeft(): int
    {
        return 0;
    }

    public function sleep(): void
    {
    }

    public function reset(): void
    {
    }

    public function gapType(): GapType
    {
        throw new LogicException('Method not available for class NoopGapDetector');
    }
}
