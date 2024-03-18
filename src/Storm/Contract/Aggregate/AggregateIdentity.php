<?php

declare(strict_types=1);

namespace Storm\Contract\Aggregate;

interface AggregateIdentity
{
    public static function fromString(string $aggregateId): static;

    public function toString(): string;

    public function equalsTo(self $aggregateId): bool;
}
