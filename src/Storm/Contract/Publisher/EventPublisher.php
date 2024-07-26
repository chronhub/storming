<?php

declare(strict_types=1);

namespace Storm\Contract\Publisher;

use Storm\Contract\Message\DomainEvent;

interface EventPublisher extends MarshallEventPublisher
{
    /**
     * Record a collection of domain events.
     *
     * @param iterable<DomainEvent> $streamEvents
     */
    public function record(iterable $streamEvents): void;

    /**
     * Pull a collection of domain events.
     *
     * @return iterable<DomainEvent>
     */
    public function pull(): iterable;

    /**
     * Flush the event publisher.
     */
    public function flush(): void;
}
