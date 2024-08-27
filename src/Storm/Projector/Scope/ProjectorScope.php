<?php

declare(strict_types=1);

namespace Storm\Projector\Scope;

use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Message\DomainEvent;

interface ProjectorScope
{
    /**
     * Return the current event if it was acknowledged.
     */
    public function event(): ?DomainEvent;

    /**
     * Return the current user state if it was set.
     */
    public function userState(): ?UserState;

    /**
     * Stop the projection.
     */
    public function stop(): void;

    /**
     * Return the current stream name
     */
    public function streamName(): string;

    /**
     * Return the clock implementation.
     */
    public function clock(): SystemClock;
}
