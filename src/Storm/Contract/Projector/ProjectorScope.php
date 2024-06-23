<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Storm\Contract\Clock\SystemClock;

interface ProjectorScope
{
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
