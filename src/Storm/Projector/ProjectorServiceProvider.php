<?php

declare(strict_types=1);

namespace Storm\Projector;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Storm\Contract\Projector\ProjectorManager;
use Storm\Projector\Connector\ConnectionManager;
use Storm\Projector\Connector\ConnectorManager;
use Storm\Projector\Connector\DatabaseConnector;
use Storm\Projector\Connector\InMemoryConnector;
use Storm\Projector\Connector\ManageConnector;
use Storm\Projector\Factory\EmitterFactory;
use Storm\Projector\Factory\Factory;
use Storm\Projector\Factory\ProviderResolver;
use Storm\Projector\Factory\QueryFactory;
use Storm\Projector\Factory\ReadModelFactory;
use Storm\Projector\Factory\Resolver;

class ProjectorServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected string $projector = __DIR__.'/../../../config/projector.php';

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->projector => config_path('projector.php')],
                'config'
            );

            $this->commands(config('projector.console.commands', []));
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->projector, 'projector');
        $this->registerManager();
        $this->registerResolver();
    }

    protected function registerManager(): void
    {
        $this->app->singleton(ConnectorManager::class, function (Application $app) {
            return tap(new ManageConnector($app), function (ConnectorManager $manager) {
                $this->registerConnectors($manager);
            });
        });

        $this->app->singleton(ProjectorManager::class, ManageProjector::class);
        $this->app->alias(ProjectorManager::class, 'projector.manager');
    }

    protected function registerResolver(): void
    {
        $this->app->singleton(Resolver::class, function (Application $app) {
            return tap(new ProviderResolver($app), function (Resolver $resolver) {
                $this->registerFactories($resolver);
            });
        });
    }

    protected function registerConnectors(ConnectorManager $manager): void
    {
        //$manager->addConnector('in_memory', fn (Application $app) => $app[InMemoryConnector::class]);
        //$manager->addConnector('in_memory-incremental', fn (Application $app) => $app[InMemoryConnector::class]);
        $manager->addConnector('pgsql', fn (Application $app) => $app[DatabaseConnector::class]);
    }

    protected function registerFactories(Resolver $resolver): void
    {
        $resolver->register('query', fn (ConnectionManager $manager): Factory => new QueryFactory($manager));
        $resolver->register('emitter', fn (ConnectionManager $manager): Factory => new EmitterFactory($manager));
        $resolver->register('read_model', fn (ConnectionManager $manager): Factory => new ReadModelFactory($manager));
    }

    public function provides(): array
    {
        return [
            'projector.manager',
            ProjectorManager::class,
            Resolver::class,
            ConnectorManager::class,
        ];
    }
}
