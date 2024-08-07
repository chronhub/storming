<?php

declare(strict_types=1);

namespace Storm\Chronicler\InMemory;

use Storm\Chronicler\Direction;
use Storm\Contract\Aggregate\AggregateIdentity;
use Storm\Contract\Chronicler\InMemoryQueryFilter;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\EventHeader;

final readonly class RetrieveAllInMemoryQueryFilter implements InMemoryQueryFilter
{
    public function __construct(
        private AggregateIdentity $aggregateId,
        private Direction $direction
    ) {}

    public function apply(): callable
    {
        return fn (DomainEvent $event): ?DomainEvent => $this->matchAggregateId($event) ? $event : null;
    }

    public function orderBy(): Direction
    {
        return $this->direction;
    }

    private function matchAggregateId(DomainEvent $event): bool
    {
        $aggregateId = $event->header(EventHeader::AGGREGATE_ID);

        if (! $aggregateId instanceof AggregateIdentity) {
            $aggregateId = $this->aggregateId::fromString($aggregateId);
        }

        return $this->aggregateId->equalsTo($aggregateId);
    }
}
