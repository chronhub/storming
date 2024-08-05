<?php

declare(strict_types=1);

namespace Storm\Projector\Filter;

use Illuminate\Database\Query\Builder;
use Storm\Stream\StreamPosition;

final class FromIncludedPosition implements DatabaseProjectionQueryFilter, LoadLimiterQueryFilter
{
    private StreamPosition $streamPosition;

    private LoadLimiter $loadLimiter;

    public function apply(): callable
    {
        return function (Builder $query): void {
            $query
                ->where('position', '>=', $this->streamPosition->value)
                ->orderBy('position')
                ->limit($this->loadLimiter->value);
        };
    }

    public function setStreamPosition(StreamPosition $streamPosition): void
    {
        $this->streamPosition = $streamPosition;
    }

    public function setLoadLimiter(LoadLimiter $loadLimiter): void
    {
        $this->loadLimiter = $loadLimiter;
    }
}
