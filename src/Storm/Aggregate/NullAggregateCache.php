<?php

declare(strict_types=1);

namespace Storm\Aggregate;

use Storm\Contract\Aggregate\AggregateCache;
use Storm\Contract\Aggregate\AggregateIdentity;
use Storm\Contract\Aggregate\AggregateRoot;

final class NullAggregateCache implements AggregateCache
{
    public function get(AggregateIdentity $aggregateIdentity): ?AggregateRoot
    {
        return null;
    }

    public function has(AggregateIdentity $aggregateIdentity): bool
    {
        return false;
    }

    /** @codeCoverageIgnore */
    public function put(AggregateRoot $aggregateRoot): void {}

    /** @codeCoverageIgnore */
    public function forget(AggregateIdentity $aggregateIdentity): void {}
}
