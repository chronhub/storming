<?php

declare(strict_types=1);

namespace Storm\Projector\Filter;

use Illuminate\Database\Query\Builder;
use Storm\Contract\Projector\ProjectionQueryFilterConnection;

final class FromIncludedPosition implements ProjectionQueryFilterConnection
{
    private int $streamPosition;

    public function apply(): callable
    {
        return function (Builder $query): void {
            $query
                ->where('position', '>=', $this->streamPosition)
                ->orderBy('position');
        };
    }

    public function setStreamPosition(int $streamPosition): void
    {
        $this->streamPosition = $streamPosition;
    }
}
