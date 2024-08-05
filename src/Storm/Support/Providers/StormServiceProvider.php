<?php

declare(strict_types=1);

namespace Storm\Support\Providers;

use Illuminate\Support\AggregateServiceProvider;
use Storm\Clock\ClockServiceProvider;
use Storm\Projector\ProjectorServiceProvider;
use Storm\Serializer\SerializerServiceProvider;

class StormServiceProvider extends AggregateServiceProvider
{
    protected $providers = [
        ClockServiceProvider::class,
        SerializerServiceProvider::class,
        ProjectorServiceProvider::class,
    ];
}
