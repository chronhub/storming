<?php

declare(strict_types=1);

namespace Storm\Contract\Chronicler;

use Illuminate\Database\Connection;

interface ChroniclerConnectionAware
{
    public function setConnection(Connection $connection): void;

    public function connection(): Connection;
}
