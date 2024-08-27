<?php

declare(strict_types=1);

namespace Storm\Contract\Aggregate;

use Generator;
use stdClass;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Message\DomainEvent;

interface AggregateRepository
{
    /**
     * Retrieve an aggregate root by its identity.
     */
    public function retrieve(AggregateIdentity $aggregateId): ?AggregateRoot;

    /**
     * Store an aggregate root.
     */
    public function store(AggregateRoot $aggregateRoot): void;

    /**
     * Retrieve an aggregate root by its identity and filter.
     */
    public function retrieveFiltered(AggregateIdentity $aggregateId, QueryFilter $queryFilter): ?AggregateRoot;

    /**
     * Retrieve the history an aggregate root by its identity and filter.
     *
     * @return Generator<DomainEvent|array|stdClass>
     */
    public function retrieveHistory(AggregateIdentity $aggregateId, ?QueryFilter $queryFilter): Generator;
}
