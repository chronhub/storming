<?php

declare(strict_types=1);

namespace Storm\Projector\Filter;

use Illuminate\Database\Query\Builder;
use Storm\Contract\Projector\ProjectionQueryFilterConnection;
use Storm\Stream\StreamPosition;

final class FromIncludedPosition implements ProjectionQueryFilterConnection
{
    private StreamPosition $streamPosition;

    public function apply(): callable
    {
        return function (Builder $query): void {
            $query
                ->where('position', '>=', $this->streamPosition->value)
                ->orderBy('position');
        };
    }

    public function setStreamPosition(StreamPosition $streamPosition): void
    {
        $this->streamPosition = $streamPosition;
    }
}
