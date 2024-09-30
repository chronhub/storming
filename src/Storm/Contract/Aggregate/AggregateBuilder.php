<?php

declare(strict_types=1);

namespace Storm\Contract\Aggregate;

interface AggregateBuilder
{
    /**
     * Create a aggregate repository instance.
     */
    public function create(): AggregateRepository;
}
