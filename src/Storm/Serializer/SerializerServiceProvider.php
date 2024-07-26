<?php

declare(strict_types=1);

namespace Storm\Serializer;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Storm\Contract\Serializer\JsonSerializer;

class SerializerServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton(JsonSerializer::class, JsonSerializerFactory::class);

        $this->app->alias(JsonSerializer::class, 'storm.serializer');
    }

    public function provides(): array
    {
        return [JsonSerializer::class, 'storm.serializer'];
    }
}
