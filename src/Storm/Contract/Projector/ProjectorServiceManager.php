<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

interface ProjectorServiceManager
{
    /**
     * Create a new projector manager instance.
     */
    public function create(string $name): ProjectorManagerInterface;

    /**
     * Extends the projector manager instance.
     *
     * @return static
     */
    public function extend(string $name, callable $callback): ProjectorServiceManager;

    /**
     * Set the default driver name to use by the manager.
     */
    public function setDefaultDriver(string $driver): ProjectorServiceManager;

    /**
     * Get the current driver name used by the manager.
     */
    public function getDefaultDriver(): string;
}
