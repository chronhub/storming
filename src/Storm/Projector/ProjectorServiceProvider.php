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
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->projector, 'projector');
        $this->registerManager();
        $this->registerFactories();
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
        $this->app->singleton(Resolver::class, function (Application $app) {
            $resolver = new ProviderResolver($app);

            $resolver->register('query', function (ConnectionManager $manager): Factory {
                return new QueryFactory($manager);
            });

            $resolver->register('emitter', function (ConnectionManager $manager): Factory {
                return new EmitterFactory($manager);
            });

            $resolver->register('read_model', function (ConnectionManager $manager): Factory {
                return new ReadModelFactory($manager);
            });

            return $resolver;
        });
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
