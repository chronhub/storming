<?php

declare(strict_types=1);

namespace Storm\Projector\Checkpoint;

use Storm\Projector\Exception\LogicException;

interface GapRecognition
{
    /**
     * Check if a gap is recoverable.
     */
    public function recover(): bool;

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
     * @return int<0, max>
     */
    public function retryLeft(): int;

    /**
     * Get the gap type depends on the retry left.
     * If no gap was detected, return null.
     */
    public function gapType(): ?GapType;

    /**
     * Sleep when a gap is detected.
     *
     * @throws LogicException when no gap is detected or no retry left.
     */
    public function sleep(): void;

    /**
     * Reset gaps detection.
     */
    public function reset(): void;
}
