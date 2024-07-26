<?php

declare(strict_types=1);

namespace Storm\Projector\Filter;

use Storm\Contract\Projector\LoadLimiterProjectionQueryFilter;
use Storm\Contract\Projector\ProjectionQueryScope;

final class InMemoryQueryScope implements ProjectionQueryScope
{
    public function fromIncludedPosition(): LoadLimiterProjectionQueryFilter
    {
        return new InMemoryFromIncludedPositionWithLoadLimiter();
    }
}
