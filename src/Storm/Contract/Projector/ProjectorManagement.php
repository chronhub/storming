<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Storm\Projector\Connector\ConnectionManager;
use Storm\Projector\Connector\Connector;

interface ProjectorManagement
{
    /**
     * Create a connection to the projector.
     */
    public function connection(?string $name = null): ConnectionManager;

    /**
     * Add a new connector to the projector.
     *
     * @param Closure(Application): Connector $connector
     */
    public function addConnector(string $name, Closure $connector): void;

    /**
     * Check if a connection is resolved.
     */
    public function connected(string $name): bool;

    /**
     * Get the default connection name from the config.
     */
    public function getDefaultDriver(): string;
}
