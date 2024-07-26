<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Agent;

use Storm\Projector\Workflow\Timer;

class TimeAgent
{
    // todo remove encapsulation of timer
    public function __construct(protected readonly Timer $timer) {}

    /**
     * Start the timer.
     */
    public function start(): void
    {
        $this->timer->start();
    }

    /**
     * Check if the timer is started.
     */
    public function isStarted(): bool
    {
        return $this->timer->isStarted();
    }

    /**
     * Reset the timer.
     */
    public function reset(): void
    {
        $this->timer->reset();
    }

    /**
     * Get the timer started timestamp.
     */
    public function getStartedTime(): int
    {
        return $this->timer->getStartedTimestamp();
    }

    /**
     * Get the timer elapsed timestamp.
     */
    public function getElapsedTime(): int
    {
        return $this->timer->getElapsedTime();
    }

    /**
     * Get the timer current timestamp.
     */
    public function getCurrentTimestamp(): int
    {
        return $this->timer->getCurrentTimestamp();
    }
}
