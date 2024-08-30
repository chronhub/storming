<?php

declare(strict_types=1);

namespace Storm;

use Illuminate\Support\ServiceProvider;
use Storm\Message\DefaultChainMessageDecorator;

class LaraStormServiceProvider extends ServiceProvider
{
    protected string $configPath = __DIR__.'/../../config/storm.php';

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->configPath => config_path('storm.php'),
            ], 'config');
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->configPath, 'storm');

        $this->app->singleton('storm.message_decorator.chain', DefaultChainMessageDecorator::class);
        $this->app->singleton('storm.event_decorator.chain', DefaultChainMessageDecorator::class);
    }

    public function provides(): array
    {
        return [
            'storm.message_decorator.chain',
            'storm.event_decorator.chain',
        ];
    }
}
