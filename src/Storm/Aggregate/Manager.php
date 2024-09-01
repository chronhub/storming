<?php

declare(strict_types=1);

namespace Storm\Aggregate;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Storm\Aggregate\Connector\Connector;
use Storm\Contract\Aggregate\AggregateRepository;

interface Manager
{
    /**
     * Create a new aggregate repository instance.
     */
    public function create(string $name, string $connector): AggregateRepository;

    /**
     * Add a new aggregate repository connector.
     *
     * @param (Closure(Application): Connector) $connector
     */
    public function addConnector(string $name, Closure $connector): void;
}
