<?php

declare(strict_types=1);

namespace Storm\Projector\Stream\Filter;

use Storm\Contract\Chronicler\QueryFilter;
use Storm\Stream\StreamPosition;

interface ProjectionQueryFilter extends QueryFilter
{
    /**
     * Set the current stream position.
     */
    public function setStreamPosition(StreamPosition $streamPosition): void;
}
