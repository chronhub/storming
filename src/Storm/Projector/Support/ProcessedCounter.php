<?php

declare(strict_types=1);

namespace Storm\Projector\Support;

use Storm\Projector\Exception\InvalidArgumentException;

final class ProcessedCounter
{
    /** @var int<0, max> */
    protected int $count = 0;

    /**
     * @param positive-int $threshold
     */
    public function __construct(public readonly int $threshold)
    {
        /** @phpstan-ignore-next-line */
        if ($threshold < 1) {
            throw new InvalidArgumentException('Processed counter threshold must be greater than 0');
        }
    }

    /**
     * Increment the processed counter.
     */
    public function increment(): void
    {
        $this->count++;
    }

    /**
     * Reset the processed counter.
     */
    public function reset(): void
    {
        $this->count = 0;
    }

    /**
     * Get the processed counter.
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     * Check if the processed counter is at the threshold.
     */
    public function isLimitReached(): bool
    {
        return $this->count >= $this->threshold;
    }
}
