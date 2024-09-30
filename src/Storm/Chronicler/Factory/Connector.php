<?php

declare(strict_types=1);

namespace Storm\Chronicler\Factory;

interface Connector
{
    /**
     * Create a new connection manager.
     */
    public function connect(array $config): ConnectionManager;
}
