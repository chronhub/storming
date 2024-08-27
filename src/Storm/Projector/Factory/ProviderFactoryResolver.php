<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Storm\Projector\Connector\ConnectionManager;
use Storm\Projector\Exception\ConfigurationViolation;

final class ProviderFactoryResolver implements ProviderFactoryRegistry
{
    /** @var array<string, string|class-string<ProviderFactory>|(Closure(ConnectionManager, Application): ProviderFactory)|ProviderFactory>|array */
    private array $factories = [];

    public function __construct(private readonly Application $app) {}

    public function resolve(string $name, ConnectionManager $connectionManager): ProviderFactory
    {
        $factory = $this->factories[$name] ?? null;

        if (! $factory) {
            throw ConfigurationViolation::message("Provider factory $name not found");
        }

        if ($factory instanceof ProviderFactory) {
            return $factory;
        }

        if ($factory instanceof Closure) {
            return $factory($connectionManager, $this->app);
        }

        // checkMe remove
        if ($this->app->bound($factory)) {
            /** @var ProviderFactory $concrete */
            $concrete = $this->app[$factory];

            if ($concrete instanceof ProviderConnectionAware) {
                $concrete->setConnection($connectionManager);
            }

            return $concrete;
        }

        return new $factory($connectionManager);
    }

    public function register(string $name, string|Closure|ProviderFactory $factory): void
    {
        $this->factories[$name] = $factory;
    }

    public function has(string $name): bool
    {
        return isset($this->factories[$name]);
    }
}
