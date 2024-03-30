<?php

declare(strict_types=1);

namespace Storm\Chronicler\Connection;

use Illuminate\Database\Connection;

trait HasConnectionAwareTrait
{
    protected Connection $connection;

    public function setConnection(Connection $connection): void
    {
        $this->connection = $connection;
    }

    public function connection(): Connection
    {
        return $this->connection;
    }
}
