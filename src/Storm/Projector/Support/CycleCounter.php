<?php

declare(strict_types=1);

namespace Storm\Projector\Support;

final class CycleCounter
{
    /** @var int<0, max> */
    protected int $cycle = 0;

    /**
     * Increment the cycle.
     */
    public function next(): void
    {
        $this->cycle++;
    }

    /**
     * Reset the cycle.
     */
    public function reset(): void
    {
        $this->cycle = 0;
    }

    /**
     * Check if the cycle is the first one.
     */
    public function isFirst(): bool
    {
        return $this->cycle === 1;
    }

    /**
     * Get the current cycle.
     */
    public function current(): int
    {
        return $this->cycle;
    }

    /**
     * Check if the cycle has started.
     */
    public function hasStarted(): bool
    {
        return $this->cycle > 0;
    }
}
