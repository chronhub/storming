<?php

declare(strict_types=1);

namespace Storm\Aggregate;

use Storm\Contract\Aggregate\AggregateIdentity;

final readonly class GenericAggregateId implements AggregateIdentity
{
    use UuidV4Generator;
}
