<?php

declare(strict_types=1);

namespace Storm\Projector\Stream\Filter;

use Storm\Chronicler\Direction;
use Storm\Contract\Chronicler\InMemoryQueryFilter;
use Storm\Contract\Message\DomainEvent;
use Storm\Projector\Support\ExtractEventHeaderTrait;
use Storm\Stream\StreamPosition;

final class InMemoryLimitByOneQuery implements InMemoryQueryFilter, ProjectionQueryFilter
{
    use ExtractEventHeaderTrait;

    private StreamPosition $streamPosition;

    public function apply(): callable
    {
        return function (DomainEvent $event): bool {
            return $this->extractInternalPosition($event)
                ->equalsTo($this->streamPosition->value);
        };
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
