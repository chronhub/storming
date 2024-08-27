<?php

declare(strict_types=1);

namespace Storm\Contract\Chronicler;

use Illuminate\Database\Connection;

interface DatabaseChronicler extends Chronicler
{
    /**
     * Get the database connection instance.
     */
    public function getConnection(): Connection;
}
