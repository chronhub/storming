<?php

declare(strict_types=1);

namespace Storm\Support\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Storm\Contract\Tracker\MessageTracker;
use Storm\Support\ContainerAsClosure;
use Storm\Tracker\TrackMessage;

class GenericServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->bind(ContainerAsClosure::class, function (Application $app) {
            return new ContainerAsClosure(fn () => $app);
        });

        // checkMe required if we use reference on tracker constructor
        $this->app->bind(MessageTracker::class, TrackMessage::class);
    }

    public function provides(): array
    {
        return [ContainerAsClosure::class, MessageTracker::class];
    }
}
