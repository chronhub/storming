<?php

declare(strict_types=1);

namespace Storm\Chronicler;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Storm\Chronicler\Connection\ToDomainEventConverter;
use Storm\Chronicler\Publisher\InMemoryEventPublisher;
use Storm\Contract\Chronicler\StreamEventConverter;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Serializer\StreamEventSerializer;
use Storm\Message\ChainMessageDecorator;
use Storm\Message\Decorator\EventId;
use Storm\Message\Decorator\EventTime;
use Storm\Message\Decorator\EventType;
use Storm\Reporter\Subscriber\CorrelationHeaderCommand;
use Storm\Serializer\DomainEventSerializer;
use Storm\Serializer\JsonSerializerFactory;
use Storm\Serializer\MessageContentSerializer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;

class ChroniclerServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function boot(): void
    {
    }

    public function register(): void
    {
        $this->registerEventDecorators();
        $this->registerStreamEventSerializer();
        $this->registerEventPublisher();

        $this->app->bind(StreamEventConverter::class, ToDomainEventConverter::class);

        // fixMe: should be handled by a share attribute in stream subscriber
        $this->app->singleton(CorrelationHeaderCommand::class);
    }

    private function registerEventDecorators(): void
    {
        $this->app->bind('event.decorator.chain.default', function (Application $app) {
            return new ChainMessageDecorator(
                new EventId(),
                new EventType(),
                $app[EventTime::class],
            );
        });
    }

    private function registerStreamEventSerializer(): void
    {
        $this->app->bind(StreamEventSerializer::class, function (Application $app) {
            $factory = new JsonSerializerFactory();

            $dateNormalizer = new DateTimeNormalizer([
                DateTimeNormalizer::FORMAT_KEY => $app[SystemClock::class]->getFormat(),
                DateTimeNormalizer::TIMEZONE_KEY => 'UTC',
            ]);

            $factory->withNormalizer($dateNormalizer, new UidNormalizer());

            return new DomainEventSerializer($factory->create(), new MessageContentSerializer());
        });
    }

    private function registerEventPublisher(): void
    {
        // todo move to his own repository
        $this->app->singleton('event.publisher.in_memory', fn () => new InMemoryEventPublisher());
    }

    public function provides(): array
    {
        return [
            'event.publisher.in_memory',
            StreamEventSerializer::class,
            StreamEventConverter::class,
            'event.decorator.chain.default',
        ];
    }
}
