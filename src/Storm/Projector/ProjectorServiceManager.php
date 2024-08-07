<?php

declare(strict_types=1);

namespace Storm\Projector;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Storm\Projector\Connector\ConnectionManager;
use Storm\Projector\Connector\Connector;
use Storm\Projector\Exception\InvalidArgumentException;

use function is_array;

class ProjectorServiceManager
{
    /** @var array<string, Connector|Closure(Application): Connector> */
    protected array $connectors = [];

    /** @var array<string, ConnectionManager>|array */
    protected array $connections = [];

    public function __construct(protected Application $app) {}

    public function connection(?string $name = null): ConnectionManager
    {
        $name = $name ?? $this->getDefaultDriver();

        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        if (! isset($this->connectors[$name])) {
            throw new InvalidArgumentException("No connector named $name found.");
        }

        $config = config("projector.connection.$name");

        if (! is_array($config) || $config === []) {
            throw new InvalidArgumentException("No configuration found for connector $name.");
        }

        return $this->connections[$name] = $this->resolveConnector($name, $config);
    }

    /**
     * @param Closure(Application): Connector $connector
     */
    public function addConnector(string $name, Closure $connector): void
    {
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

    protected function resolveConnector(string $name, array $config): ConnectionManager
    {
        $connector = $this->connectors[$name];

        $this->app['config']->set('projector.default', $name);

        return $connector($this->app)->connect($config);
    }
}
