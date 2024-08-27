<?php

declare(strict_types=1);

namespace Storm\Tests\Domain\Balance;

use Storm\Aggregate\UuidV4Generator;
use Storm\Contract\Aggregate\AggregateIdentity;

final class BalanceId implements AggregateIdentity
{
    use UuidV4Generator;
}
