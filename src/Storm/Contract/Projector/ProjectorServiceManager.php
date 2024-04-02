<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

interface ProjectorServiceManager
{
    /**
     * Create a new projector manager instance.
     */
    public function create(string $name): self;

    /**
     * Extends the projector manager instance.
     */
    public function extend(string $name, callable $callback): self;

    /**
     * Set the default driver name to use by the manager.
     */
    public function setDefaultDriver(string $driver): self;

    /**
     * Get the current driver name used by the manager.
     */
    public function getDefaultDriver(): string;
}
