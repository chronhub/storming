<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Watcher;

use function count;
use function in_array;

class AckedEventWatcher
{
    protected array $events = [];

    protected int $count = 0;

    /**
     * Add unique event to the list of acked events.
     */
    public function ack(string $eventClass): void
    {
        if (! in_array($eventClass, $this->events, true)) {
            $this->events[] = $eventClass;
        }

        $this->count++;
    }

    public function reset(): void
    {
        $this->events = [];
        $this->count = 0;
    }

    /**
     * Check if there are any acked events.
     */
    public function hasEvents(): bool
    {
        return $this->events !== [];
    }

    /**
     * Get all acked and unique events.
     */
    public function events(): array
    {
        return $this->events;
    }

    /**
     * Count the number of unique acked events.
     */
    public function countUnique(): int
    {
        return count($this->events);
    }

    /**
     * Count the total number of acked events.
     */
    public function count(): int
    {
        return $this->count;
    }
}
