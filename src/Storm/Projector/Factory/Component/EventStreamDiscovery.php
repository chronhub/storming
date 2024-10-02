<?php

declare(strict_types=1);

namespace Storm\Projector\Factory\Component;

use Storm\Contract\Chronicler\EventStreamProvider;

class EventStreamDiscovery
{
    /** @var array|array<string> */
    protected array $streamDiscovered = [];

    public function __construct(
        protected readonly EventStreamProvider $eventStreamProvider
    ) {}

    /**
     * @param callable(EventStreamProvider): array<string> $query
     */
    public function discover(callable $query): array
    {
        return $this->streamDiscovered = $query($this->eventStreamProvider);
    }
}
