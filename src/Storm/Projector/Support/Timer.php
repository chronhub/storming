<?php

declare(strict_types=1);

namespace Storm\Projector\Support;

use Storm\Clock\PointInTime;
use Storm\Contract\Clock\SystemClock;
use Storm\Projector\Exception\RuntimeException;

/**
 * fixMe we use timestamp to measure the time of the projection
 *  but we should use ms for time measurement, specially for tests
 * todo move to timestampMs
 */
class Timer
{
    protected ?PointInTime $startTime = null;

    public function __construct(protected readonly SystemClock $clock) {}

    /**
     * Starts the timer
     *
     * @throws RuntimeException when the timer is already started
     */
    public function start(): void
    {
        if ($this->isStarted()) {
            throw new RuntimeException('Timer is already started');
        }

        $this->startTime = $this->clock->now();
    }

    /**
     * Resets the timer
     */
    public function reset(): void
    {
        $this->startTime = null;
    }

    /**
     * Returns true if the timer is started
     */
    public function isStarted(): bool
    {
        return $this->startTime instanceof PointInTime;
    }

    /**
     * Returns the timestamp of the timer start.
     *
     * @return int<0, max>
     *
     * @throws RuntimeException when the timer is not started
     */
    public function getStartedTimestamp(): int
    {
        $this->assertTimerIsStarted();

        return $this->startTime->carbon()->getTimestamp();
    }

    /**
     * Returns the elapsed time since the timer start.
     *
     * @return int<0, max>
     *
     * @throws RuntimeException when the timer is not started
     */
    public function getElapsedTime(): int
    {
        $this->assertTimerIsStarted();

        return $this->clock->now()->carbon()->getTimestamp() - $this->getStartedTimestamp();
    }

    /**
     * Returns the current timestamp.
     *
     * @return int<0, max>
     */
    public function getCurrentTimestamp(): int
    {
        return $this->clock->now()->carbon()->getTimestamp();
    }

    /**
     * Asserts that the timer is started.
     *
     * @throws RuntimeException when the timer is not started
     */
    private function assertTimerIsStarted(): void
    {
        if (! $this->isStarted()) {
            throw new RuntimeException('Timer is not started');
        }
    }
}
