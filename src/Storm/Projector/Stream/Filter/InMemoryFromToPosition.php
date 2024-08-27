<?php

declare(strict_types=1);

namespace Storm\Projector\Stream\Filter;

use Storm\Chronicler\Direction;
use Storm\Contract\Chronicler\InMemoryQueryFilter;
use Storm\Contract\Message\DomainEvent;
use Storm\Projector\Support\ExtractEventHeaderTrait;
use Storm\Stream\StreamPosition;

final class InMemoryFromToPosition implements InMemoryQueryFilter, LoadLimiterQueryFilter, ProjectionQueryFilter
{
    use ExtractEventHeaderTrait;

    private StreamPosition $streamPosition;

    private LoadLimiter $loadLimiter;

    public function apply(): callable
    {
        return function (DomainEvent $event): bool {
            return $this->extractInternalPosition($event)->isBetween(
                $this->streamPosition->value,
                $this->streamPosition->value + $this->loadLimiter->value - 1,
            );
        };
    }

    public function setLoadLimiter(LoadLimiter $loadLimiter): void
    {
        $this->loadLimiter = $loadLimiter;
    }

    public function setStreamPosition(StreamPosition $streamPosition): void
    {
        $this->streamPosition = $streamPosition;
    }

    public function orderBy(): Direction
    {
        return Direction::FORWARD;
    }
}
