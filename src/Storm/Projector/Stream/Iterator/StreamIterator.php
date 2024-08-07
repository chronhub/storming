<?php

declare(strict_types=1);

namespace Storm\Projector\Stream\Iterator;

use ArrayIterator;
use Countable;
use Generator;
use Iterator;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\EventHeader;
use Storm\Stream\StreamPosition;

use function iterator_to_array;

final class StreamIterator implements Countable, Iterator
{
    private ?DomainEvent $event = null;

    private ?StreamPosition $position = null;

    /** @var ArrayIterator<DomainEvent> */
    private ArrayIterator $streamEvents;

    public function __construct(Generator $streamEvents)
    {
        $this->streamEvents = new ArrayIterator(iterator_to_array($streamEvents));

        $this->next();
    }

    public function current(): ?DomainEvent
    {
        return $this->event;
    }

    public function next(): void
    {
        $this->event = $this->streamEvents->current();

        if ($this->event instanceof DomainEvent) {
            $internalPosition = $this->event->header(EventHeader::INTERNAL_POSITION);
            $this->position = StreamPosition::fromValue($internalPosition);
        } else {
            $this->position = null;
            $this->event = null;
        }

        $this->streamEvents->next();
    }

    public function key(): ?StreamPosition
    {
        return $this->position;
    }

    public function rewind(): void
    {
        $this->streamEvents->rewind();

        $this->next();
    }

    public function valid(): bool
    {
        return $this->event instanceof DomainEvent;
    }

    public function count(): int
    {
        return $this->streamEvents->count();
    }
}
