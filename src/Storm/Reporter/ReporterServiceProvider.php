<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class ReporterServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {

    }

    public function provides(): array
    {
        return [];
    }
}
