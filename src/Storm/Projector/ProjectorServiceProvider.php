<?php

declare(strict_types=1);

namespace Storm\Projector;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Storm\Chronicler\Connection\EventStreamProvider;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\ProjectorManagerInterface;
use Storm\Contract\Projector\SubscriptionFactory;
use Storm\Contract\Serializer\JsonSerializer;
use Storm\Projector\Factory\ConnectionSubscriptionFactory;
use Storm\Projector\Filter\QueryScopeConnection;
use Storm\Projector\Options\DefaultOption;
use Storm\Projector\Repository\ConnectionProjectionProvider;
use Storm\Serializer\JsonSerializerFactory;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

class ProjectorServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->registerJsonSerializer();

        $this->registerProviders();

        $this->registerSubscriptionFactory();

        $this->registerProjectorManager();
    }

    public function provides(): array
    {
        return [
            'event_stream.provider.connection',
            'projection.provider.connection',
            'projector.subscription_factory.connection',
            'projection.serializer.json.default',
            ProjectorManagerInterface::class,
        ];
    }

    private function registerSubscriptionFactory(): void
    {
        $this->app->singleton('projector.subscription_factory.connection', function (Application $app): SubscriptionFactory {
            return new ConnectionSubscriptionFactory(
                $app['chronicler.event.transactional.standard.pgsql'],
                $app['projection.provider.connection'],
                $app['event_stream.provider.connection'],
                $app[SystemClock::class],
                $app['projection.serializer.json.default'],
                $app[Dispatcher::class],
                $app[QueryScopeConnection::class],
                new DefaultOption(signal: true, retries: [])
            );
        });
    }

    private function registerProjectorManager(): void
    {
        $this->app->singleton(ProjectorManagerInterface::class, function (Application $app): ProjectorManagerInterface {
            return new ProjectorManager($app['projector.subscription_factory.connection']);
        });
    }

    private function registerJsonSerializer(): void
    {
        $this->app->singleton('projection.serializer.json.default', function (Application $app): JsonSerializer {
            $factory = new JsonSerializerFactory();
            $factory->withEncodeOptions(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
            $factory->withDecodeOptions(JSON_BIGINT_AS_STRING);

            $dateNormalizer = new DateTimeNormalizer([
                DateTimeNormalizer::FORMAT_KEY => $app[SystemClock::class]->getFormat(),
                DateTimeNormalizer::TIMEZONE_KEY => 'UTC',
            ]);

            $factory->withNormalizer($dateNormalizer);

            return $factory;
        });
    }

    private function registerProviders(): void
    {
        $this->app->bind('event_stream.provider.connection', EventStreamProvider::class);

        $this->app->bind('projection.provider.connection', ConnectionProjectionProvider::class);
    }
}
