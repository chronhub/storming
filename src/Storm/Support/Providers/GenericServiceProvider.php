<?php

declare(strict_types=1);

namespace Storm\Support\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Storm\Support\ContainerAsClosure;

class GenericServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->bind(ContainerAsClosure::class, function (Application $app) {
            return new ContainerAsClosure(fn () => $app);
        });
    }

    public function provides(): array
    {
        return [ContainerAsClosure::class];
    }
}
