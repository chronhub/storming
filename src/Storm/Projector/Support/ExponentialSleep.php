<?php

declare(strict_types=1);

namespace Storm\Projector\Support;

use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Exception\LogicException;

use function min;
use function usleep;

class ExponentialSleep
{
    /** @var int<1, max> */
    protected int $sleepTime;

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
         * The maximum sleep time in milliseconds.
         *
         * @var positive-int
         */
        public readonly int $max = 50000,
    ) {
        $this->assertValidSleep();

        $this->reset();
    }

    /**
     * Sleep for the current sleep time.
     */
    public function sleep(): void
    {
        usleep($this->sleepTime * 1000);
    }

    /**
     * Increment the sleep time by the growth factor.
     *
     * When the current sleep time exceeds the max sleep time,
     * the sleep time will be reset to the base sleep time.
     */
    public function increment(): void
    {
        $this->sleepTime = $this->sleepTime >= $this->max
            ? $this->base
            : min((int) ($this->sleepTime * $this->factor), $this->max);
    }

    /**
     * Reset the sleep time to the base sleep time.
     */
    public function reset(): void
    {
        $this->sleepTime = $this->base;
    }

    /**
     * Get the current sleep time in milliseconds.
     */
    public function getSleepingTime(): int
    {
        return $this->sleepTime;
    }

    /**
     * @throws InvalidArgumentException when constructed parameters are invalid
     */
    protected function assertValidSleep(): void
    {
        // @phpstan-ignore-next-line
        if ($this->base < 1) {
            throw new InvalidArgumentException('Base sleep time must be greater than 0');
        }

        if ($this->factor < 1) {
            throw new InvalidArgumentException('Growth factor must be greater or equal than 1');
        }

        if ($this->base >= $this->max) {
            throw new LogicException('Base sleep time must be less than to max sleep time');
        }
    }
}
