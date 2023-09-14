<?php

declare(strict_types=1);

namespace Storm\Support\Providers;

use Illuminate\Support\AggregateServiceProvider;
use Storm\Message\MessageServiceProvider;
use Storm\Reporter\ReporterServiceProvider;

class StormServiceProvider extends AggregateServiceProvider
{
    protected $providers = [
        GenericServiceProvider::class,
        MessageServiceProvider::class,
        ReporterServiceProvider::class,
    ];
}
