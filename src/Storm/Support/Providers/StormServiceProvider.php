<?php

declare(strict_types=1);

namespace Storm\Support\Providers;

use Illuminate\Support\AggregateServiceProvider;
use Storm\Message\MessageServiceProvider;

class StormServiceProvider extends AggregateServiceProvider
{
    protected $providers = [
        GenericServiceProvider::class,
        MessageServiceProvider::class,
    ];
}
