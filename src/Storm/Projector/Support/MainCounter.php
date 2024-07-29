<?php

declare(strict_types=1);

namespace Storm\Projector\Support;

final class MainCounter
{
    /** @var int<0, max> */
    protected int $count = 0;

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
        $this->count = 0;
    }

    /**
     * Get the current main counter.
     */
    public function current(): int
    {
        return $this->count;
    }
}
