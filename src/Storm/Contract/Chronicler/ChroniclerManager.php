<?php

declare(strict_types=1);

namespace Storm\Contract\Chronicler;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Storm\Chronicler\Factory\Connector;

interface ChroniclerManager
{
    /**
     * Create a new event store instance.
     */
    public function create(string $name): Chronicler;

    /**
     * Add a new event store connector.
     *
     * @param (Closure(Application): Connector) $connector
     */
    public function addConnector(string $name, Closure $connector): void;

    /**
     * Determine if the event store is connected.
     */
    public function connected(string $name): bool;
}
