<?php

declare(strict_types=1);

namespace Storm\Projector\Stream\Filter;

use Illuminate\Database\Query\Builder;
use Storm\Stream\StreamPosition;

use function str_contains;

final class FromIncludedPosition implements DatabaseProjectionQueryFilter, LoadLimiterQueryFilter, StreamNameAwareQueryFilter
{
    private ?StreamPosition $streamPosition;

    private ?LoadLimiter $loadLimiter;

    // fixMe checkpoint should hold an instance  of StreamName
    private ?string $streamName;

    public function apply(): callable
    {
        return function (Builder $query): void {
            $query
                ->when(str_contains($this->streamName, '-'),
                    function (Builder $query): void {
                        $query
                            ->whereNotNull('internal_position')
                            ->where('internal_position', '>=', $this->streamPosition->value);
                    }, function (Builder $query): void {
                        $query->where('position', '>=', $this->streamPosition->value);
                    })
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

    public function setStreamName(string $streamName): void
    {
        $this->streamName = $streamName;
    }
}
