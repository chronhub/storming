<?php

declare(strict_types=1);

namespace Storm\Serializer;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class SerializerServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton(SerializerFactory::class);
    }

    public function provides(): array
    {
        return [SerializerFactory::class];
    }
}
