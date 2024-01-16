<?php

declare(strict_types=1);

namespace Storm\Message;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Storm\Contract\Message\MessageFactory;
use Storm\Contract\Serializer\MessageSerializer;
use Storm\Serializer\JsonSerializer;
use Storm\Serializer\MessageContentSerializer;
use Storm\Serializer\MessagingSerializer;

class MessageServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton(MessageSerializer::class, function () {
            return new MessagingSerializer(
                (new JsonSerializer())->create(),
                new MessageContentSerializer()
            );
        });

        $this->app->singleton(MessageFactory::class, GenericMessageFactory::class);
    }

    public function provides(): array
    {
        return [
            MessageSerializer::class,
            MessageFactory::class,
        ];
    }
}
