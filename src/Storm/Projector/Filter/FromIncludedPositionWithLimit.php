<?php

declare(strict_types=1);

namespace Storm\Projector\Filter;

use Illuminate\Database\Query\Builder;
use Storm\Contract\Projector\LoadLimiterProjectionQueryFilter;
use Storm\Contract\Projector\ProjectionQueryFilterConnection;

final class FromIncludedPositionWithLimit implements LoadLimiterProjectionQueryFilter, ProjectionQueryFilterConnection
{
    private int $streamPosition;

    private int $loadLimiter;

    public function apply(): callable
    {
        return function (Builder $query): void {
            $query
                ->where('position', '>=', $this->streamPosition)
                ->orderBy('position')
                ->limit($this->loadLimiter);
        };
    }

    public function setStreamPosition(int $streamPosition): void
    {
        $this->streamPosition = $streamPosition;
    }

    public function setLoadLimiter(int $loadLimiter): void
    {
        $this->loadLimiter = $loadLimiter;
    }
}
