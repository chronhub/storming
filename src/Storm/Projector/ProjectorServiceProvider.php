<?php

declare(strict_types=1);

namespace Storm\Projector;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Storm\Chronicler\InMemory\InMemoryAutoIncrementEventStore;
use Storm\Chronicler\InMemory\InMemoryEventStore;
use Storm\Chronicler\InMemory\InMemoryEventStreamProvider;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Projector\MonitoringManager;
use Storm\Contract\Projector\ProjectorManagerInterface;
use Storm\Contract\Serializer\JsonSerializer;
use Storm\Contract\Serializer\SymfonySerializer;
use Storm\Projector\Connector\InMemoryConnector;
use Storm\Projector\Connector\SubscriptionFactoryResolver;
use Storm\Projector\Repository\InMemoryProjectionProvider;

class ProjectorServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public array $singletons = [
        'chronicler.provider.in_memory' => InMemoryEventStreamProvider::class,
        'projector.provider.in_memory' => InMemoryProjectionProvider::class,
    ];

    protected string $projector = __DIR__.'/../../../config/projector.php';

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([$this->projector => config_path('projector.php')], 'config');

            $commands = config('projector.console.commands', []);
            $this->commands($commands);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->projector, 'projector');

        $this->registerJsonSerializer();

        $this->registerEventStoreService();

        $this->registerManagers();
    }

    public function provides(): array
    {
        return [
            'projector.provider.in_memory',
            'chronicler.provider.in_memory',
            'chronicler.provider.in_memory.auto_increment',
            'chronicler.in_memory',
            'projector.serializer.json',
            'projector.manager',
            ProjectorManagerInterface::class,
            ProjectorServiceManager::class,
        ];
    }

    protected function registerManagers(): void
    {
        $this->app->singleton(ProjectorServiceManager::class, function (Application $app) {
            $projector = new ProjectorServiceManager($app);

            $projector->addConnector('in_memory', fn (Application $app) => new InMemoryConnector($app));
            $projector->addConnector('in_memory-auto-increment', fn (Application $app) => new InMemoryConnector($app));

            return $projector;
        });

        $this->app->singleton(ProjectorManagerInterface::class, function (Application $app): ProjectorManagerInterface {
            return new ProjectorManager(
                $app[ProjectorServiceManager::class],
                new SubscriptionFactoryResolver(),
            );
        });

        $this->app->alias(ProjectorManagerInterface::class, 'projector.manager');

        $this->app->singleton(MonitoringManager::class, function (Application $app) {
            return new MonitorManager($app[ProjectorServiceManager::class]);
        });
    }

    protected function registerJsonSerializer(): void
    {
        $this->app->bind('projector.serializer.json', function (Application $app): SymfonySerializer {
            /** @var JsonSerializer $factory */
            $factory = $app['storm.serializer'];

            return $factory
                ->withEncodeOptions(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_FORCE_OBJECT)
                ->withDecodeOptions(JSON_OBJECT_AS_ARRAY | JSON_BIGINT_AS_STRING)
                ->create();
        });
    }

    protected function registerEventStoreService(): void
    {
        $this->app->singleton('chronicler.in_memory', function (Application $app): Chronicler {
            $provider = $app['chronicler.provider.in_memory'];

            return new InMemoryEventStore($provider);
        });

        $this->app->singleton('chronicler.in_memory.auto_increment', function (Application $app): Chronicler {
            $provider = $app['chronicler.provider.in_memory'];

            return new InMemoryAutoIncrementEventStore($provider);
        });
    }
}
