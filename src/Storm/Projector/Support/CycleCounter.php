<?php

declare(strict_types=1);

namespace Storm\Projector\Support;

final class CycleCounter
{
    protected int $cycle = 0;

    /**
     * Start the cycle.
     *
     * @deprecated use next() instead
     */
    public function start(): void
    {
        $this->cycle = 1;
    }

    /**
     * Increment the cycle.
     */
    public function next(): void
    {
        $this->cycle++;
    }

    /**
     * Get the current cycle.
     */
    public function current(): int
    {
        return $this->cycle;
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
     * Check if the cycle has started.
     */
    public function hasStarted(): bool
    {
        return $this->cycle > 0;
    }
}
