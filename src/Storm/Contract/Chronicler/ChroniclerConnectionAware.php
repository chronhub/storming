<?php

declare(strict_types=1);

namespace Storm\Contract\Chronicler;

use Illuminate\Database\Connection;

interface ChroniclerConnectionAware
{
    /**
     * Set the connection to the chronicler.
     */
    public function setConnection(Connection $connection): void;

    /**
     * Get the connection from the chronicler.
     */
    public function connection(): Connection;
}
