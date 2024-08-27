<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Projector\Connector\ConnectionManager;

interface ProviderConnectionAware
{
    /**
     * Set the connection manager to a provider factory.
     */
    public function setConnection(ConnectionManager $connection): void;
}
