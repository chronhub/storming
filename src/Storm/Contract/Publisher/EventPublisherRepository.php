<?php

declare(strict_types=1);

namespace Storm\Contract\Publisher;

use Storm\Contract\Message\DomainEvent;

interface EventPublisherRepository extends EventPublisher
{
    /**
     * Record a collection of domain events.
     *
     * @param iterable<DomainEvent> $streamEvents
     */
    public function record(iterable $streamEvents): void;

    /**
     * Delete all recorded domain events.
     */
    public function deletePendingEvents(): void;
}
