<?php

declare(strict_types=1);

namespace Storm\Clock;

use Storm\Contract\Clock\SystemClock;

trait ClockAwareTrait
{
    protected SystemClock $clock;

    public function setClock(SystemClock $clock): void
    {
        $this->clock = $clock;
    }
}
