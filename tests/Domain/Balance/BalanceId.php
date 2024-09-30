<?php

declare(strict_types=1);

namespace Storm\Tests\Domain\Balance;

use Storm\Aggregate\Identity\AggregateIdentityV4Generator;
use Storm\Contract\Aggregate\AggregateIdentity;

final class BalanceId implements AggregateIdentity
{
    use AggregateIdentityV4Generator;
}
