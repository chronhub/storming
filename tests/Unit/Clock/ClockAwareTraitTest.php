<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Clock;

use Error;
use Storm\Clock\Clock;
use Storm\Clock\ClockAwareTrait;
use Storm\Contract\Clock\SystemClock;

test('clock aware trait', function () {
    $clock = new Clock();

    $clockAware = new class
    {
        use ClockAwareTrait;

        public function clock(): SystemClock
        {
            return $this->clock;
        }
    };

    $clockAware->setClock($clock);

    expect($clockAware->clock())->toBe($clock);
});

test('raises error exception if clock is not set', function () {
    $clockAware = new class
    {
        use ClockAwareTrait;

        public function clock(): SystemClock
        {
            return $this->clock;
        }
    };

    $clockAware->clock();
})->throws(Error::class);
