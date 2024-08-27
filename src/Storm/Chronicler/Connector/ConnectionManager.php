<?php

declare(strict_types=1);

namespace Storm\Chronicler\Connector;

use Storm\Contract\Chronicler\Chronicler;

interface ConnectionManager
{
    /**
     * Create a new event store instance.
     */
    public function create(): Chronicler;
}
