<?php

declare(strict_types=1);

namespace Storm\Projector\Filter;

use Storm\Contract\Projector\LoadLimiterProjectionQueryFilter;
use Storm\Contract\Projector\ProjectionQueryFilterConnection;
use Storm\Contract\Projector\ProjectionQueryScope;

final class QueryScopeConnection implements ProjectionQueryScope
{
    public function fromIncludedPosition(): ProjectionQueryFilterConnection
    {
        return new FromIncludedPosition();
    }

    public function fromIncludedPositionWithLoadLimiter(): LoadLimiterProjectionQueryFilter&ProjectionQueryFilterConnection
    {
        return new FromIncludedPositionWithLoadLimiter();
    }
}
