<?php

declare(strict_types=1);

namespace Storm\Projector\Filter;

use Storm\Chronicler\Direction;
use Storm\Contract\Chronicler\InMemoryQueryFilter;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\EventHeader;
use Storm\Contract\Projector\ProjectionQueryFilter;

final class InMemoryLimitByOneQuery implements InMemoryQueryFilter, ProjectionQueryFilter
{
    private int $streamPosition = 0;

    public function apply(): callable
    {
        return fn (DomainEvent $event): bool => (int) $event->header(EventHeader::INTERNAL_POSITION) === $this->streamPosition;
    }

    public function setStreamPosition(int $streamPosition): void
    {
        $this->streamPosition = $streamPosition;
    }

    public function orderBy(): Direction
    {
        return Direction::FORWARD;
    }
}
