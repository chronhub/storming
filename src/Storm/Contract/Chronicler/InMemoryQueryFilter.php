<?php

declare(strict_types=1);

namespace Storm\Contract\Chronicler;

use Storm\Chronicler\Direction;

interface InMemoryQueryFilter extends QueryFilter
{
    /**
     * Get the direction of the order.
     */
    public function orderBy(): Direction;
}
