<?php

declare(strict_types=1);

namespace Storm\Projector;

use Illuminate\Contracts\Foundation\Application;
use Storm\Projector\Connector\ConnectionManager;
use Storm\Projector\Connector\Connector;
use Storm\Projector\Exception\InvalidArgumentException;

class ProjectorServiceManager
{
    /** @var array<string, Connector> */
    protected array $connectors = [];

    public function __construct(protected Application $app) {}

    public function connection(?string $name = null): ConnectionManager
    {
        $name = $name ?? $this->getDefaultDriver();

        if (! isset($this->connectors[$name])) {
            throw new InvalidArgumentException("No connector named $name found.");
        }

        $config = config("projector.connection.$name");

        if (! $config) {
            throw new InvalidArgumentException("No configuration found for connector $name.");
        }

        return $this->connectors[$name]->connect($config);
    }

    public function addConnector(string $name, Connector $connector): void
    {
        $this->connectors[$name] = $connector;
    }

    public function getDefaultDriver(): string
    {
        return config('projector.default');
    }
}
