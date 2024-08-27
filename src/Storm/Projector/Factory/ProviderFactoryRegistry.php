<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Storm\Projector\Connector\ConnectionManager;
use Storm\Projector\Exception\ConfigurationViolation;

interface ProviderFactoryRegistry
{
    /**
     * Create a provider factory.
     *
     * @throws ConfigurationViolation when the provider factory is not found
     */
    public function resolve(string $name, ConnectionManager $connectionManager): ProviderFactory;

    /**
     * Register a provider factory.
     *
     * It will override any existing provider factory with the same name.
     *
     * First, when using bound factories resolved through the container,
     * you should ensure that the factory implements ProviderConnectionAware contract
     * to set the connection manager.
     *
     * @see ProviderConnectionAware
     *
     * @param string|(Closure(ConnectionManager, Application): ProviderFactory)|ProviderFactory $factory
     */
    public function register(string $name, string|Closure|ProviderFactory $factory): void;

    /**
     * Check if name registers a provider factory.
     */
    public function has(string $name): bool;
}
