<?php

declare(strict_types=1);

namespace Storm\Chronicler;

use Illuminate\Support\ServiceProvider;

class ChroniclerApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes/event_stream_api.php');
    }

    public function register(): void
    {
    }
}
