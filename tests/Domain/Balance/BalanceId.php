<?php

declare(strict_types=1);

namespace Storm\Tests\Domain\Balance;

use Storm\Aggregate\AggregateIdV4Trait;
use Storm\Contract\Aggregate\AggregateIdentity;
use Symfony\Component\Uid\Uuid;

final class BalanceId implements AggregateIdentity
{
    use AggregateIdV4Trait;

    public static function create(): self
    {
        return new self(Uuid::v4());
    }
}
