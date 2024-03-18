<?php

declare(strict_types=1);

namespace Storm\Annotation;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class AnnotationServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected string $configPath = __DIR__.'./config/annotation.php';

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([$this->configPath => config_path('annotation.php')], 'config');
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->configPath, 'annotation');

        $this->app->singleton(Kernel::class);

        $this->app->singleton(KernelStorage::class, function () {
            return $this->getKernel()->getStorage();
        });
    }

    public function provides(): array
    {
        return [Kernel::class, KernelStorage::class];
    }

    protected function getKernel(): Kernel
    {
        return $this->app[Kernel::class];
    }
}
