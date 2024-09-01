<?php

declare(strict_types=1);

namespace Storm\Aggregate;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;
use Storm\Aggregate\Connector\Connector;
use Storm\Contract\Aggregate\AggregateRepository;

use function is_array;

final class RepositoryManager implements Manager
{
    /** @var array<string, (Closure(Application): Connector)>|array */
    private array $connectors = [];

    /** @var array<string, AggregateRepository>|array */
    private array $repositories = [];

    public function __construct(private readonly Application $app) {}

    public function create(string $name, string $connector): AggregateRepository
    {
        if (isset($this->repositories[$name])) {
            return $this->repositories[$name];
        }

        $config = $this->getConfig($name);

        return $this->repositories[$name] = $this->resolve($config, $connector);
    }

    public function addConnector(string $name, Closure $connector): void
    {
        $this->connectors[$name] = $connector;
    }

    private function resolve(array $config, string $connector): AggregateRepository
    {
        $instance = $this->getConnector($connector);

        return $instance->connect($config)->create();
    }

    private function getConnector(string $name): Connector
    {
        if (! isset($this->connectors[$name])) {
            throw new InvalidArgumentException("No connector found for aggregate key [$name]");
        }

        return $this->connectors[$name]($this->app);
    }

    private function getConfig(string $name): array
    {
        $config = $this->app['config']->get("storm.aggregates.$name");

        if (! is_array($config) || $config === []) {
            throw new InvalidArgumentException("No config found for aggregate key [$name]");
        }

        return $config;
    }
}
