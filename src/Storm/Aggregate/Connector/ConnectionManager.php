<?php

declare(strict_types=1);

namespace Storm\Aggregate\Connector;

use Storm\Contract\Aggregate\AggregateRepository;

interface ConnectionManager
{
    /**
     * Create a new event store instance.
     */
    public function create(): AggregateRepository;
}
