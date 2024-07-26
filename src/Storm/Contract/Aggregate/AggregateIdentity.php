<?php

declare(strict_types=1);

namespace Storm\Contract\Aggregate;

interface AggregateIdentity
{
    /**
     * Return an aggregate identity from a string.
     */
    public static function fromString(string $aggregateId): static;

    /**
     * Return a string representation of the aggregate identity.
     */
    public function toString(): string;

    /**
     * Check if the given aggregate identity is equal to the current one.
     */
    public function equalsTo(self $aggregateId): bool;
}
