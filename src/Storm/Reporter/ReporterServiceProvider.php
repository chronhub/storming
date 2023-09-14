<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Storm\Contract\Reporter\ReporterManager;

class ReporterServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton(ReporterManager::class, ManageReporter::class);
    }

    public function provides(): array
    {
        return [ReporterManager::class];
    }
}
