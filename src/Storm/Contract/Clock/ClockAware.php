<?php

declare(strict_types=1);

namespace Storm\Contract\Clock;

interface ClockAware
{
    /**
     * Set the clock.
     */
    public function setClock(SystemClock $clock): void;
}
