<?php

declare(strict_types=1);

namespace Storm\Aggregate;

use Storm\Contract\Aggregate\AggregateIdentity;
use Symfony\Component\Uid\Uuid;

trait AggregateIdV4Trait
{
    public function __construct(public readonly Uuid $id)
    {
    }

    public static function fromString(string $aggregateId): static
    {
        return new static(Uuid::fromString($aggregateId));
    }

    public function toString(): string
    {
        return $this->id->jsonSerialize();
    }

    public function equalsTo(AggregateIdentity $aggregateId): bool
    {
        return $aggregateId instanceof $this && $this->id->equals($aggregateId->id);
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
