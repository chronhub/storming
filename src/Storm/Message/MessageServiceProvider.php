<?php

declare(strict_types=1);

namespace Storm\Message;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Storm\Contract\Message\MessageFactory;
use Storm\Contract\Serializer\MessageSerializer;
use Storm\Serializer\MessagingSerializer;
use Storm\Serializer\SerializerFactory;

class MessageServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->registerMessageSerializer();

        $this->app->singleton(MessageFactory::class, GenericMessageFactory::class);
        $this->app->alias(MessageFactory::class, 'message.factory.default');
    }

    protected function registerMessageSerializer(): void
    {
        $this->app->singleton(MessageSerializer::class, function (Application $app): MessageSerializer {
            $factory = new SerializerFactory($app);

            $config = config('storm.serializer.messaging');

            return new MessagingSerializer($factory->create($config));
        });
    }

    public function provides(): array
    {
        return [
            MessageSerializer::class,
            MessageFactory::class,
            'message.factory.default',
        ];
    }
}
