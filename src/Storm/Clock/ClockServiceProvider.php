<?php

declare(strict_types=1);

namespace Storm\Clock;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Storm\Contract\Clock\SystemClock;

class ClockServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton(SystemClock::class, Clock::class);
        $this->app->alias(SystemClock::class, 'storm.clock');
    }

    public function provides(): array
    {
        return [SystemClock::class];
    }
}
