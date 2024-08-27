<?php

declare(strict_types=1);

namespace Storm\Projector\Repository;

use Storm\Clock\PointInTime;
use Storm\Contract\Clock\SystemClock;
use Storm\Projector\Exception\RuntimeException;

class LockManager
{
    protected ?PointInTime $lockedUntil = null;

    /**
     * @param positive-int $lockTimeout   The duration for which a lock is valid, in milliseconds
     * @param int<0, max>  $lockThreshold The duration after which a lock should be refreshed, in milliseconds
     */
    public function __construct(
        protected readonly SystemClock $clock,
        public readonly int $lockTimeout,
        public readonly int $lockThreshold
    ) {}

    /**
     * Acquires a lock and returns the new lock value.
     */
    public function acquire(): string
    {
        $this->lockedUntil = $this->clock->now();

        return $this->current();
    }

    /**
     * Refreshes the lock and returns the new lock value.
     */
    public function refresh(): string
    {
        $this->lockedUntil = $this->clock->now()->add('milliseconds', $this->lockTimeout);

        return $this->current();
    }

    /**
     * Determines whether the lock should be refreshed.
     */
    public function shouldRefresh(): bool
    {
        if ($this->lockedUntil === null || $this->lockThreshold === 0) {
            return true;
        }

        $adjustedLock = $this->lockedUntil->add('milliseconds', $this->lockThreshold);

        return $adjustedLock < $this->clock->now();
    }

    /**
     * Returns the current lock value.
     *
     * @throws RuntimeException When lock is not acquired
     */
    public function current(): string
    {
        if ($this->lockedUntil === null) {
            throw new RuntimeException('Lock is not acquired');
        }

        return $this->lockedUntil->format();
    }
}
