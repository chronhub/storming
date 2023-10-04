<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Storm\Contract\Reporter\ReporterManager;
use Storm\Contract\Reporter\Router;
use Storm\Contract\Reporter\SubscriberManager;

use function array_merge;

class ReporterServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function boot(): void
    {
        // check from config if we should autoload reporters
        $this->getReporterManager()->wire();
        $this->getSubscriberManager()->wire();
    }

    public function register(): void
    {
        $this->app->singleton(ReporterManager::class, ReportingManager::class);
        $this->app->singleton(Router::class, MessageManager::class);
        $this->app->singleton(SubscriberManager::class, SubscribingManager::class);
    }

    public function provides(): array
    {
        return array_merge(
            [ReporterManager::class, MessageManager::class, SubscriberManager::class],
            $this->getReporterManager()->provides(),
            $this->getSubscriberManager()->provides()
        );
    }

    protected function getReporterManager(): ReportingManager
    {
        return $this->app[ReportingManager::class];
    }

    protected function getSubscriberManager(): SubscriberManager
    {
        return $this->app[SubscriberManager::class];
    }
}
