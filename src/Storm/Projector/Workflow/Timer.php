<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow;

use DateTimeImmutable;
use Storm\Contract\Clock\SystemClock;

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

    public function getTimestamp(): int
    {
        return $this->startTime->getTimestamp();
    }

    public function getElapsedTime(): int
    {
        return $this->clock->now()->getTimestamp() - $this->getTimestamp();
    }
}
