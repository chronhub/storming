<?php

declare(strict_types=1);

namespace Storm\Projector\Filter;

use Storm\Contract\Chronicler\QueryFilter;

interface LoadLimiterQueryFilter extends QueryFilter
{
    /**
     * Set the number of stream events to load per stream.
     */
    public function setLoadLimiter(LoadLimiter $loadLimiter): void;
}
