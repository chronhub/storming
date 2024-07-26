<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Agent;

use Storm\Contract\Chronicler\EventStreamProvider;

use function array_diff;
use function count;

class EventStreamDiscoveryAgent
{
    /** @var array<string>|array */
    protected array $streamDiscovered = [];

    /** @var array<string>|array */
    protected array $newStreamDiscovered = [];

    public function __construct(protected readonly EventStreamProvider $eventStreamProvider) {}

    /**
     * Discover new event streams.
     */
    public function discover(callable $query): array
    {
        $eventStreams = $query($this->eventStreamProvider);

        $this->newStreamDiscovered = array_diff($eventStreams, $this->streamDiscovered);

        return $this->streamDiscovered = $eventStreams;
    }

    /**
     * Check if there are any new event streams.
     */
    public function hasEventStreams(): bool
    {
        return count($this->streamDiscovered) > 0;
    }

    /**
     * Get new event streams.
     */
    public function newEventStreams(): array
    {
        return $this->newStreamDiscovered;
    }

    /**
     * Reset new event streams.
     */
    public function resetNewEventStreams(): void
    {
        $this->newStreamDiscovered = [];
    }
}
