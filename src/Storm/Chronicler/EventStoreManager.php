<?php

declare(strict_types=1);

namespace Storm\Chronicler;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Storm\Chronicler\Exceptions\ConfigurationViolation;
use Storm\Chronicler\Exceptions\InvalidArgumentException;
use Storm\Chronicler\Factory\Connector;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\ChroniclerManager;

use function is_array;

final class EventStoreManager implements ChroniclerManager
{
    /** @var array<string, Closure(Application): Connector>|array */
    private array $connectors = [];

    /** @var array<string, Chronicler>|array */
    private array $connections = [];

    public function __construct(private Application $app) {}

    public function create(string $name): Chronicler
    {
        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        if (! isset($this->connectors[$name])) {
            throw new InvalidArgumentException("No event store connector named $name found.");
        }

        return $this->connections[$name] = $this->resolve($name, $this->getConfiguration($name));
    }

    public function addConnector(string $name, Closure $connector): void
    {
        $this->connectors[$name] = $connector;
    }

    public function connected(string $name): bool
    {
        return isset($this->connections[$name]);
    }

    protected function resolve(string $name, array $config): Chronicler
    {
        $connector = $this->connectors[$name]($this->app, $config);

        if (! $connector instanceof Connector) {
            throw new ConfigurationViolation('Connector must return an instance of '.Connector::class.' interface.');
        }

        return $connector->connect($config)->create();
    }

    protected function getConfiguration(string $name): array
    {
        $config = $this->app['config']->get("chronicler.store.connection.$name");

        if (! is_array($config) || $config === []) {
            throw new ConfigurationViolation("No event store configuration found for $name.");
        }

        return $config;
    }
}
