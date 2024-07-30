<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Agent;

use Storm\Contract\Chronicler\EventStreamProvider;

class EventStreamDiscoveryAgent
{
    /** @var array<string>|array */
    protected array $streamDiscovered = [];

    public function __construct(protected readonly EventStreamProvider $eventStreamProvider) {}

    /**
     * Discover new event streams.
     */
    public function discover(callable $query): array
    {
        return $this->streamDiscovered = $query($this->eventStreamProvider);
    }
}
