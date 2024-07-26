<?php

declare(strict_types=1);

namespace Storm\Projector\Support;

final class MainCounter
{
    /** @var int<0, max> */
    protected int $count = 0;

    protected bool $doNotReset = false;

    /**
     * Increment the main counter.
     */
    public function increment(): void
    {
        $this->count++;
    }

    /**
     * Reset the main counter.
     */
    public function reset(): void
    {
        if (! $this->doNotReset) {
            $this->count = 0;
        }
    }

    /**
     * Get the current main counter.
     */
    public function current(): int
    {
        return $this->count;
    }

    /**
     * Set the doNotReset flag.
     */
    public function doNotReset(bool $doNotReset): void
    {
        $this->doNotReset = $doNotReset;
    }

    /**
     * Check if the doNotReset flag is set.
     */
    public function isDoNotReset(): bool
    {
        return $this->doNotReset;
    }
}
