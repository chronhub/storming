<?php

declare(strict_types=1);

namespace Storm\Support\Providers;

use Illuminate\Support\AggregateServiceProvider;
use Storm\Annotation\AnnotationServiceProvider;
use Storm\Chronicler\ChroniclerServiceProvider;
use Storm\Clock\ClockServiceProvider;
use Storm\Message\MessageServiceProvider;
use Storm\Reporter\ReporterServiceProvider;

class StormServiceProvider extends AggregateServiceProvider
{
    protected $providers = [
        AnnotationServiceProvider::class,
        ClockServiceProvider::class,
        MessageServiceProvider::class,
        ReporterServiceProvider::class,
        ChroniclerServiceProvider::class,
    ];
}
