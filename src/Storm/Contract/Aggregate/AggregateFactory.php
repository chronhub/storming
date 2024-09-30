<?php

declare(strict_types=1);

namespace Storm\Contract\Aggregate;

interface AggregateFactory
{
    /**
     * Create a new aggregate factory instance from the given configuration.
     */
    public function make(array $config): AggregateBuilder;
}
