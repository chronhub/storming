<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow;

use DateTimeImmutable;
use Storm\Contract\Clock\SystemClock;
use Storm\Projector\Exception\RuntimeException;

/**
 * fixMe we use timestamp to measure the time of the projection
 *  but we should use ms for time measurement, specially for tests
 */
class Timer
{
    protected ?DateTimeImmutable $startTime = null;

    public function __construct(protected readonly SystemClock $clock)
    {
    }

    public function start(): void
    {
        if (! $this->startTime instanceof DateTimeImmutable) {
            $this->startTime = $this->clock->now();
        }
    }

    public function reset(): void
    {
        $this->startTime = null;
    }

    public function isStarted(): bool
    {
        return $this->startTime instanceof DateTimeImmutable;
    }

    public function getStartedTimestamp(): int
    {
        $this->assertTimerIsStarted();

        return $this->startTime->getTimestamp();
    }

    public function getElapsedTime(): int
    {
        $this->assertTimerIsStarted();

        return $this->clock->now()->getTimestamp() - $this->getStartedTimestamp();
    }

    public function getCurrentTimestamp(): int
    {
        return $this->clock->now()->getTimestamp();
    }

    private function assertTimerIsStarted(): void
    {
        if (! $this->isStarted()) {
            throw new RuntimeException('Timer is not started');
        }
    }
}
