<?php

declare(strict_types=1);

namespace Storm\Projector\Filter;

use Storm\Chronicler\Direction;
use Storm\Contract\Chronicler\InMemoryQueryFilter;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Projector\ProjectionQueryFilter;
use Storm\Projector\Support\ExtractEventHeaderTrait;

final class InMemoryLimitByOneQuery implements InMemoryQueryFilter, ProjectionQueryFilter
{
    use ExtractEventHeaderTrait;

    private int $streamPosition = 0;

    public function apply(): callable
    {
        return fn (DomainEvent $event): bool => $this->extractInternalPosition($event) === $this->streamPosition;
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
