<?php

declare(strict_types=1);

namespace Storm\Projector\Support;

use Storm\Contract\Message\DomainEvent;

use function in_array;

/**
 * @template TEvent of DomainEvent
 */
final class AckedCounter
{
    /** @var array<class-string<TEvent>> */
    protected array $streamEvents = [];

    /** @var int<0, max> */
    protected int $count = 0;

    /**
     * Merge unique stream events and increment the counter.
     *
     * @param class-string<TEvent> $streamEvent
     */
    public function merge(string $streamEvent): void
    {
        if (! in_array($streamEvent, $this->streamEvents, true)) {
            $this->streamEvents[] = $streamEvent;
        }

        $this->count++;
    }

    /**
     * Reset the acked counter.
     */
    public function reset(): void
    {
        $this->streamEvents = [];
        $this->count = 0;
    }

    /**
     * Return the total number of acked events.
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     * Return the list of acked events.
     *
     * @return array<class-string<TEvent>>
     */
    public function getEvents(): array
    {
        return $this->streamEvents;
    }
}
