<?php

declare(strict_types=1);

namespace Storm\Aggregate\Identity;

use Storm\Contract\Aggregate\AggregateIdentity;

final readonly class GenericAggregateId implements AggregateIdentity
{
    use AggregateIdentityV4Generator;
}
