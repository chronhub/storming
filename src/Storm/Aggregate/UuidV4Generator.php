<?php

declare(strict_types=1);

namespace Storm\Aggregate;

use Storm\Contract\Aggregate\AggregateIdentity;
use Symfony\Component\Uid\Uuid;

/**
 * @phpstan-require-implements AggregateIdentity
 *
 * @property-read Uuid $id
 */
trait UuidV4Generator
{
    use AggregateIdTrait;

    public static function create(): self
    {
        return new self(Uuid::v4());
    }

    public function generate(): string
    {
        return self::create()->toString();
    }
}
