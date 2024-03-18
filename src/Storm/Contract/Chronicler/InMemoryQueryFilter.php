<?php

declare(strict_types=1);

namespace Storm\Contract\Chronicler;

use Storm\Chronicler\Direction;

interface InMemoryQueryFilter extends QueryFilter
{
    public function orderBy(): Direction;
}
