<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Watcher;

use Storm\Projector\Workflow\Timer;

class TimeWatcher
{
    public function __construct(protected readonly Timer $timer)
    {
    }

    public function start(): void
    {
        $this->timer->start();
    }

    public function isStarted(): bool
    {
        return $this->timer->isStarted();
    }

    public function reset(): void
    {
        $this->timer->reset();
    }

    public function getStartedTime(): int
    {
        return $this->timer->getStartedTimestamp();
    }

    public function getElapsedTime(): int
    {
        return $this->timer->getElapsedTime();
    }

    public function getCurrentTime(): int
    {
        return $this->timer->getCurrentTimestamp();
    }
}
