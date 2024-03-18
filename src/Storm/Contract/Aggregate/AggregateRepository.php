<?php

declare(strict_types=1);

namespace Storm\Contract\Aggregate;

use Generator;
use Storm\Contract\Chronicler\QueryFilter;

interface AggregateRepository
{
    public function retrieve(AggregateIdentity $aggregateId): ?AggregateRoot;

    public function store(AggregateRoot $aggregateRoot): void;

    public function retrieveFiltered(AggregateIdentity $aggregateId, QueryFilter $queryFilter): ?AggregateRoot;

    public function retrieveHistory(AggregateIdentity $aggregateId, ?QueryFilter $queryFilter): Generator;
}
