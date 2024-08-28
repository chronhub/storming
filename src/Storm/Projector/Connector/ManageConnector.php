<?php

declare(strict_types=1);

namespace Storm\Projector\Connector;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Storm\Projector\Exception\ConfigurationViolation;

use function is_array;

final class ManageConnector implements ConnectorManager
{
    /**
     * The registered connectors.
     *
     * @var array<string, Closure(Application): Connector>|array
     */
    protected array $connectors = [];

    /**
     * The registered connections.
     *
     * @var array<string, ConnectionManager>|array
     */
    protected array $connections = [];

    public function __construct(private readonly Application $app) {}

    public function connection(?string $name = null): ConnectionManager
    {
        $name = $name ?? $this->getDefaultDriver();

        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        if (! isset($this->connectors[$name])) {
            throw ConfigurationViolation::withMessage("No connector named $name found.");
        }

        $config = $this->getConfiguration($name);

        return $this->connections[$name] = $this->resolveConnector($name, $config);
    }

    public function addConnector(string $name, Closure $connector): void
    {
        if (isset($this->connectors[$name])) {
            throw ConfigurationViolation::withMessage("Connector $name already exists.");
        }

        $this->connectors[$name] = $connector;
    }

    public function connected(string $name): bool
    {
        return isset($this->connections[$name]);
    }

    public function getDefaultDriver(): string
    {
        return config('projector.default');
    }

    public function setDefaultDriver(string $name): void
    {
        $this->app['config']->set('projector.default', $name);
    }

    private function resolveConnector(string $name, array $config): ConnectionManager
    {
        $connector = $this->connectors[$name];

        return $connector($this->app)->connect($config);
    }

    private function getConfiguration(string $name): array
    {
        $config = config("projector.connection.$name");

        if (! is_array($config) || $config === []) {
            throw ConfigurationViolation::withMessage("No configuration found for connector $name.");
        }

        return $config;
    }
}
