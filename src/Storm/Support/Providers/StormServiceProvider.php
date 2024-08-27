<?php

declare(strict_types=1);

namespace Storm\Support\Providers;

use Illuminate\Support\AggregateServiceProvider;
use Storm\Chronicler\ChroniclerServiceProvider;
use Storm\Clock\ClockServiceProvider;
use Storm\LaraStormServiceProvider;
use Storm\Message\MessageServiceProvider;
use Storm\Projector\ProjectorServiceProvider;
use Storm\Serializer\SerializerServiceProvider;
use Storm\Story\StoryServiceProvider;

class StormServiceProvider extends AggregateServiceProvider
{
    protected $providers = [
        LaraStormServiceProvider::class,
        ClockServiceProvider::class,
        SerializerServiceProvider::class,
        MessageServiceProvider::class,
        StoryServiceProvider::class,
        ChroniclerServiceProvider::class,
        ProjectorServiceProvider::class,
    ];
}
