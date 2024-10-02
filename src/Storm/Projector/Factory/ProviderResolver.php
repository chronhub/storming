<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Storm\Projector\Connector\ConnectionManager;
use Storm\Projector\Exception\ConfigurationViolation;

final class ProviderResolver implements Registry
{
    /** @var array<string, (Closure(ConnectionManager, Application): Factory)|Factory>|array */
    private array $factories = [];

    public function __construct(
        private readonly Application $app
    ) {}

    public function resolve(string $name, ConnectionManager $connectionManager): Factory
    {
        $factory = $this->factories[$name] ?? null;

        if (! $factory) {
            throw new ConfigurationViolation("Provider factory $name not found");
        }

        if ($factory instanceof Factory) {
            return $factory;
        }

        if ($factory instanceof Closure) {
            return $factory($connectionManager, $this->app);
        }

        throw new ConfigurationViolation("Provider factory $name not supported");
    }

    public function register(string $name, Closure|Factory $factory): void
    {
        $this->factories[$name] = $factory;
    }

    public function has(string $name): bool
    {
        return isset($this->factories[$name]);
    }
}
