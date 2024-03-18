<?php

declare(strict_types=1);

namespace Storm\Message;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Storm\Contract\Message\MessageFactory;
use Storm\Contract\Serializer\MessageSerializer;
use Storm\Message\Decorator\EventDispatched;
use Storm\Message\Decorator\EventId;
use Storm\Message\Decorator\EventTime;
use Storm\Message\Decorator\EventType;
use Storm\Serializer\JsonSerializer;
use Storm\Serializer\MessageContentSerializer;
use Storm\Serializer\MessagingSerializer;

class MessageServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->registerMessageSerializer();

        $this->registerMessageDecorator();

        $this->app->singleton(MessageFactory::class, GenericMessageFactory::class);

        $this->app->alias(MessageFactory::class, 'message.factory.default');
    }

    public function provides(): array
    {
        return [
            MessageSerializer::class,
            MessageFactory::class,
            'message.factory.default',
            'message.decorator.chain.default',
        ];
    }

    protected function registerMessageSerializer(): void
    {
        $this->app->singleton(MessageSerializer::class, function () {
            return new MessagingSerializer(
                (new JsonSerializer())->create(),
                new MessageContentSerializer()
            );
        });
    }

    protected function registerMessageDecorator(): void
    {
        $this->app->bind('message.decorator.chain.default', function (Application $app) {
            return new ChainMessageDecorator(
                new EventId(),
                new EventType(),
                $app[EventTime::class],
                new EventDispatched()
            );
        });
    }
}
