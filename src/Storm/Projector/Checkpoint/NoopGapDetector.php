<?php

declare(strict_types=1);

namespace Storm\Projector\Checkpoint;

use Storm\Contract\Projector\GapRecognition;

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
}
