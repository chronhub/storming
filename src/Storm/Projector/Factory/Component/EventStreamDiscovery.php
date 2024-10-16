<?php

declare(strict_types=1);

namespace Storm\Projector\Factory\Component;

use Storm\Contract\Chronicler\EventStreamProvider;

readonly class EventStreamDiscovery
{
    public function __construct(
        protected EventStreamProvider $eventStreamProvider
    ) {}

    /**
     * @param callable(EventStreamProvider): array<string> $query
     */
    public function discover(callable $query): array
    {
        return $query($this->eventStreamProvider);
    }
}
