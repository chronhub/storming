<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Component;

use Storm\Clock\PointInTime;
use Storm\Contract\Clock\SystemClock;
use Storm\Projector\Exception\RuntimeException;

class Timer
{
    protected ?PointInTime $startTime = null;

    public function __construct(
        protected readonly SystemClock $clock
    ) {}

    /**
     * @throws RuntimeException when the timer is already started
     */
    public function start(): void
    {
        if ($this->isStarted()) {
            throw new RuntimeException('Timer is already started');
        }

        $this->startTime = $this->clock->now();
    }

    public function reset(): void
    {
        $this->startTime = null;
    }

    /**
     * @throws RuntimeException when the timer is not started
     */
    public function getStartedTime(): PointInTime
    {
        $this->assertTimerIsStarted();

        return $this->startTime;
    }

    /**
     * Get the elapsed time in format `%s.%f`.
     */
    public function getElapsedTime(): string
    {
        $this->assertTimerIsStarted();

        $interval = $this->clock->now()->carbon()->diff($this->startTime->carbon());

        return $interval->format('%s.%f');
    }

    public function getCurrentTime(): PointInTime
    {
        return $this->clock->now();
    }

    public function isStarted(): bool
    {
        return $this->startTime instanceof PointInTime;
    }

    /**
     * @throws RuntimeException when the timer is not started
     */
    protected function assertTimerIsStarted(): void
    {
        if (! $this->isStarted()) {
            throw new RuntimeException('Timer is not started');
        }
    }
}
