<?php

declare(strict_types=1);

namespace Storm\Chronicler;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Storm\Chronicler\Database\EventStreamDatabaseProvider;
use Storm\Chronicler\Factory\Pgsql\PgsqlConnector;
use Storm\Chronicler\Factory\Pgsql\PublisherConnector;
use Storm\Contract\Chronicler\ChroniclerManager;
use Storm\Contract\Chronicler\EventStreamProvider;

class ChroniclerServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected string $configPath = __DIR__.'/../../../config/chronicler.php';

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->configPath => config_path('chronicler.php'),
            ], 'config');
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->configPath, 'chronicler');

        $this->registerManager();
        $this->registerEventStreamProvider();
    }

    protected function registerManager(): void
    {
        $this->app->singleton(ChroniclerManager::class, function (Application $app): ChroniclerManager {
            $manager = new EventStoreManager($app);
            $manager->addConnector('pgsql', fn (Application $app) => $app[PgsqlConnector::class]);
            $manager->addConnector('publisher', fn (Application $app) => $app[PublisherConnector::class]);

            return $manager;
        });

        $this->app->alias(ChroniclerManager::class, 'chronicler.manager');
    }

    protected function registerEventStreamProvider(): void
    {
        $this->app->bind('event_stream.provider.db.pgsql', function (Application $app): EventStreamProvider {
            return new EventStreamDatabaseProvider($app['db']->connection('pgsql'));
        });
    }

    public function provides(): array
    {
        return [
            ChroniclerManager::class,
            'chronicler.manager',
            'event_stream.provider.db.pgsql',
            'event.decorator.chain.default',
        ];
    }
}
