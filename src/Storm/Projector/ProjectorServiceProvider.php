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
use Storm\Projector\Factory\EmitterProviderFactory;
use Storm\Projector\Factory\ProviderFactory;
use Storm\Projector\Factory\ProviderFactoryRegistry;
use Storm\Projector\Factory\ProviderFactoryResolver;
use Storm\Projector\Factory\QueryProviderFactory;
use Storm\Projector\Factory\ReadModelProviderFactory;

class ProjectorServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public array $singletons = [
        ProjectorManager::class => ManageProjector::class,
    ];

    protected string $projector = __DIR__.'/../../../config/projector.php';

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->projector => config_path('projector.php')],
                'config'
            );

            $this->commands(config('projector.console.commands', []));

            //fixMe remove when storm install is available
            $this->loadMigrationsFrom(__DIR__.'/../../../migrations');
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->projector, 'projector');

        $this->registerManager();
        $this->registerFactories();
        $this->registerProjections();
    }

    protected function registerManager(): void
    {
        $this->app->singleton(ConnectorManager::class, function (Application $app) {
            $projector = new ManageConnector($app);

            // fixMe no need two in memory instances
            $projector->addConnector('in_memory', fn (Application $app) => $app[InMemoryConnector::class]);
            $projector->addConnector('in_memory-incremental', fn (Application $app) => $app[InMemoryConnector::class]);
            $projector->addConnector('pgsql', fn (Application $app) => $app[DatabaseConnector::class]);

            return $projector;
        });

        $this->app->alias(ProjectorManager::class, 'projector.manager');
    }

    protected function registerFactories(): void
    {
        $this->app->singleton(ProviderFactoryRegistry::class, function (Application $app) {
            $registry = new ProviderFactoryResolver($app);

            $registry->register('query', function (ConnectionManager $manager): ProviderFactory {
                return new QueryProviderFactory($manager);
            });

            $registry->register('emitter', function (ConnectionManager $manager): ProviderFactory {
                return new EmitterProviderFactory($manager);
            });

            $registry->register('read_model', function (ConnectionManager $manager): ProviderFactory {
                return new ReadModelProviderFactory($manager);
            });

            return $registry;
        });
    }

    protected function registerProjections(): void
    {
        if (! config('projector.projections.auto_discovery', false)) {
            return;
        }

        $projections = config('projector.projections.projection', []);

        foreach ($projections as $key => $builds) {
            foreach ($builds as $name => $projectionBuild) {
                $this->app->bind("projection.$key.$name", $projectionBuild);
            }
        }
    }

    public function provides(): array
    {
        return [
            'projector.manager',
            ProjectorManager::class,
            ProviderFactoryRegistry::class,
            ConnectorManager::class,
        ];
    }
}
