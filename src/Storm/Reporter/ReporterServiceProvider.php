<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

use function array_merge;

class ReporterServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function boot(): void
    {
        // check from config if we should autoload reporters
        $this->getManager()->wire();
    }

    public function register(): void
    {
        $this->app->singleton(ReporterManager::class);
    }

    public function provides(): array
    {
        return array_merge([ReporterManager::class], $this->getManager()->getLoaded());
    }

    protected function getManager(): ReporterManager
    {
        return $this->app[ReporterManager::class];
    }
}
