<?php

declare(strict_types=1);

namespace Storm\Projector\Connector;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Storm\Contract\Projector\ConnectorResolver;
use Storm\Projector\Exception\ConfigurationViolation;

use function is_array;

final class ConnectorServiceManager implements ConnectorResolver
{
    /** @var array<string, Closure(Application): Connector>|array */
    protected array $connectors = [];

    /** @var array<string, ConnectionManager>|array */
    protected array $connections = [];

    public function __construct(private readonly Application $app) {}

    public function connection(?string $name = null): ConnectionManager
    {
        $name = $name ?? $this->getDefaultDriver();

        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        if (! isset($this->connectors[$name])) {
            throw ConfigurationViolation::message("No connector named $name found.");
        }

        $config = $this->getConfiguration($name);

        return $this->connections[$name] = $this->resolveConnector($name, $config);
    }

    public function addConnector(string $name, Closure $connector): void
    {
        // prevent duplicate connectors as a connector
        // which already have been resolved will not be re-resolved
        // checkMe allow to purge an already resolved connector?
        if (isset($this->connectors[$name])) {
            throw ConfigurationViolation::message("Connector $name already exists.");
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

        //$this->setDefaultDriver($name); // checkMe do we need to do this?

        return $connector($this->app)->connect($config);
    }

    private function getConfiguration(string $name): array
    {
        $config = config("projector.connection.$name");

        if (! is_array($config) || $config === []) {
            throw ConfigurationViolation::message("No configuration found for connector $name.");
        }

        return $config;
    }
}
