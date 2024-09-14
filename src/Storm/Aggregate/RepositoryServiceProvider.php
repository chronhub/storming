<?php

declare(strict_types=1);

namespace Storm\Aggregate;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Storm\Aggregate\Connector\GenericConnector;

class RepositoryServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected string $configPath = __DIR__.'/../../../config/aggregates.php';

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->configPath => config_path('aggregates.php'),
            ], 'config');
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->configPath, 'aggregates');

        $this->app->singleton(Manager::class, function (Application $app): Manager {
            $manager = new RepositoryManager($app);
            $manager->addConnector('default', fn (Application $app) => $app[GenericConnector::class]);

            return $manager;
        });
    }

    public function provides(): array
    {
        return [Manager::class];
    }
}
