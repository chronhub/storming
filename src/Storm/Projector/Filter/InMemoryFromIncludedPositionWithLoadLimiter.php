<?php

declare(strict_types=1);

namespace Storm\Projector\Filter;

use Storm\Chronicler\Direction;
use Storm\Contract\Chronicler\InMemoryQueryFilter;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Projector\LoadLimiterProjectionQueryFilter;
use Storm\Projector\Support\ExtractEventHeaderTrait;
use Storm\Stream\StreamPosition;

final class InMemoryFromIncludedPositionWithLoadLimiter implements InMemoryQueryFilter, LoadLimiterProjectionQueryFilter
{
    use ExtractEventHeaderTrait;

    private StreamPosition $streamPosition;

    private LoadLimiter $loadLimiter;

    public function apply(): callable
    {
        return fn (DomainEvent $event): bool => $this->extractInternalPosition($event)->isBetween(
            $this->streamPosition->value,
            $this->loadLimiter->value
        );
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
