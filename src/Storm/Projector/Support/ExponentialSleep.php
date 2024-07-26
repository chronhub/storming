<?php

declare(strict_types=1);

namespace Storm\Projector\Support;

use Storm\Projector\Exception\InvalidArgumentException;

use function min;
use function usleep;

final class ExponentialSleep
{
    /** @var int<0, max> */
    protected int $currentSleepTime;

    public function __construct(
        /**
         * The base sleep time in milliseconds.
         *
         * @var positive-int
         */
        public readonly int $base = 1000,

        /**
         * The growth factor for the sleep time.
         */
        public readonly float $factor = 2,

        /**
         * The maximum sleep time in microseconds.
         *
         * @var positive-int
         */
        public readonly int $max = 1000000,
    ) {
        // @phpstan-ignore-next-line
        if ($base < 1) {
            throw new InvalidArgumentException('Base sleep time must be greater than 0');
        }

        if ($factor < 1) {
            throw new InvalidArgumentException('Growth factor must be greater or equal than 1');
        }

        // @phpstan-ignore-next-line
        if ($max < 1) {
            throw new InvalidArgumentException('Max sleep time must be greater than 0');
        }

        $this->reset();
    }

    /**
     * Sleep for the current sleep time.
     */
    public function sleep(): void
    {
        usleep($this->currentSleepTime);
    }

    /**
     * Increment the sleep time by the growth factor.
     *
     * When the current sleep time is greater than the max sleep time,
     * the sleep time will be reset to the base sleep time.
     */
    public function increment(): void
    {
        $this->currentSleepTime = $this->currentSleepTime > $this->max
            ? $this->base
            : min((int) ($this->currentSleepTime * $this->factor), $this->max);
    }

    /**
     * Reset the sleep time to the base sleep time.
     */
    public function reset(): void
    {
        $this->currentSleepTime = $this->base;
    }

    /**
     * Get the current sleep time in milliseconds.
     */
    public function getSleepingTime(): int
    {
        return $this->currentSleepTime;
    }
}
