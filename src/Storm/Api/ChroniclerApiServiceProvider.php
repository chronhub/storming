<?php

declare(strict_types=1);

namespace Storm\Chronicler\Api;

use Illuminate\Support\ServiceProvider;

class ChroniclerApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes/stream_api.php');
    }

    public function register(): void
    {
    }
}
