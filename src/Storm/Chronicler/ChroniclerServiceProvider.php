<?php

declare(strict_types=1);

namespace Storm\Chronicler;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Storm\Chronicler\Publisher\InMemoryEventPublisher;
use Storm\Contract\Serializer\StreamEventSerializer;
use Storm\Message\ChainMessageDecorator;
use Storm\Message\Decorator\EventId;
use Storm\Message\Decorator\EventTime;
use Storm\Message\Decorator\EventType;
use Storm\Reporter\Subscriber\CorrelationHeaderCommand;
use Storm\Serializer\DomainEventSerializer;
use Storm\Serializer\JsonSerializer;
use Storm\Serializer\MessageContentSerializer;

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
        $this->app->bind(StreamEventSerializer::class, function () {
            return new DomainEventSerializer(
                (new JsonSerializer())->create(),
                new MessageContentSerializer()
            );
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
            'event.decorator.chain.default',
        ];
    }
}
