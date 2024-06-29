<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Storm\Projector\Checkpoint\GapType;

interface GapRecognition
{
    /**
     * Is recoverable if a gap is detected.
     */
    public function isRecoverable(): bool;

    /**
     * Check if a gap is detected.
     */
    public function hasGap(): bool;

    /**
     * Check if it has retry when a gap is detected.
     */
    public function hasRetry(): bool;

    /**
     * Get retry left.
     *
     * @return int<0,max>
     */
    public function retryLeft(): int;

    /**
     * Get the gap type depends on the retry left.
     */
    public function gapType(): GapType;

    /**
     * Sleep when a gap is detected.
     */
    public function sleep(): void;

    /**
     * Reset gaps detection.
     */
    public function reset(): void;
}
