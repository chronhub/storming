<?php

declare(strict_types=1);

namespace Storm;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Storm\Contract\Message\MessageDecorator;
use Storm\Message\ChainMessageDecorator;

use function array_map;

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

        $this->app->singleton('storm.message_decorator.chain', function (): MessageDecorator {
            $config = config('storm.decorators.message', []);
            $decorators = array_map(fn (string $decorator) => $this->app[$decorator], $config);

            return new ChainMessageDecorator(...$decorators);
        });

        $this->app->singleton('storm.event_decorator.chain', function (Application $app): MessageDecorator {
            $config = config('storm.decorators.event', []);
            $decorators = array_map(fn (string $decorator) => $app[$decorator], $config);

            return new ChainMessageDecorator(...$decorators);
        });
    }

    public function provides(): array
    {
        return [
            'storm.message_decorator.chain',
            'storm.event_decorator.chain',
        ];
    }
}
