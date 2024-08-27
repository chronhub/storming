<?php

declare(strict_types=1);

namespace Storm\Contract\Aggregate;

use Generator;
use Storm\Contract\Message\DomainEvent;

interface AggregateRoot
{
    /**
     * Reconstitute an aggregate root from a collection of domain events.
     *
     * @param  Generator<DomainEvent> $events
     * @return $this|null
     */
    public static function reconstitute(AggregateIdentity $aggregateId, Generator $events): ?static;

    /**
     * Release all domain events that have been recorded.
     *
     * @return array<DomainEvent>|array
     */
    public function releaseEvents(): array;

    /**
     * Return the aggregate identity.
     */
    public function identity(): AggregateIdentity;

    /**
     * Return the version of the aggregate root.
     *
     * @return positive-int
     */
    public function version(): int;
}
