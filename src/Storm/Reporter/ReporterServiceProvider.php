<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Storm\Contract\Message\MessageProducer;
use Storm\Contract\Reporter\ReporterManager as Manager;
use Storm\Contract\Reporter\Routable;
use Storm\Reporter\Console\MapReporterListenerCommand;
use Storm\Reporter\Router\MessageRouter;
use Storm\Support\Facade\Report;

class ReporterServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MapReporterListenerCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->app->bind(Routable::class, MessageRouter::class);

        $this->app->singleton(Manager::class, ReporterManager::class);
        $this->app->alias(Manager::class, Report::REPORTER_ID);
    }

    public function provides(): array
    {
        return [
            Manager::class,
            Report::REPORTER_ID,
            Routable::class,
            MessageProducer::class,
        ];
    }
}
