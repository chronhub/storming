<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Storm\Projector\Filter\LoadLimiter;

interface LoadLimiterQueryFilter extends ProjectionQueryFilter
{
    /**
     * Set the number of stream events to load per stream.
     */
    public function setLoadLimiter(LoadLimiter $loadLimiter): void;
}
