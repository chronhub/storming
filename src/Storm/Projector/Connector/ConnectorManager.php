<?php

declare(strict_types=1);

namespace Storm\Projector\Connector;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Storm\Projector\Exception\ConfigurationViolation;

interface ConnectorManager
{
    /**
     * Create a connection to the projector.
     *
     * @throws ConfigurationViolation when no connection is found
     * @throws ConfigurationViolation when no configuration is found for the connection
     */
    public function connection(?string $name = null): ConnectionManager;

    /**
     * Add a new connector to the projector.
     *
     * @param Closure(Application): Connector $connector
     *
     * @throws ConfigurationViolation when the connector name already exists
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

    /*
     * Set the default connection name in the config.
     */
    public function setDefaultDriver(string $name): void;
}
