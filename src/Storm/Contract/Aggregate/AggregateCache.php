<?php

declare(strict_types=1);

namespace Storm\Contract\Aggregate;

interface AggregateCache
{
    /**
     * Retrieve an aggregate from the cache.
     */
    public function get(AggregateIdentity $aggregateIdentity): ?AggregateRoot;

    /**
     * Store an aggregate in the cache.
     */
    public function put(AggregateRoot $aggregateRoot): void;

    /**
     * Forget an aggregate from the cache.
     */
    public function forget(AggregateIdentity $aggregateIdentity): void;

    /**
     * Check if an aggregate is in the cache.
     */
    public function has(AggregateIdentity $aggregateIdentity): bool;
}
