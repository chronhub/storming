<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Storm\Contract\Chronicler\QueryFilter;

interface ProjectionQueryFilter extends QueryFilter
{
    /**
     * @param positive-int $streamPosition
     */
    public function setStreamPosition(int $streamPosition): void;
}
