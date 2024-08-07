<?php

declare(strict_types=1);

namespace Storm\Projector\Stream\Iterator;

use Countable;
use DateTimeImmutable;
use Illuminate\Support\Collection;
use Iterator;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\Header;
use Storm\Stream\StreamPosition;

final class MergeStreamIterator implements Countable, Iterator
{
    /** @var Collection<array{StreamIterator, string}> */
    private Collection $iterators;

    /** @var Collection<array{StreamIterator, string}> */
    private Collection $originalIteratorOrder;

    /** @var positive-int */
    public readonly int $numberOfIterators;

    /** @var int<0, max> */
    public readonly int $numberOfEvents;

    public function __construct(
        private readonly SystemClock $clock,
        Collection $iterators
    ) {
        $this->iterators = $iterators;
        $this->originalIteratorOrder = $this->iterators;
        $this->numberOfIterators = $this->iterators->count();
        $this->numberOfEvents = $this->iterators->sum(fn (array $stream): int => $stream[0]->count());

        $this->prioritizeIterators();
    }

    public function rewind(): void
    {
        $this->iterators->each(static function (array $stream): void {
            $stream[0]->rewind();
        });

        $this->prioritizeIterators();
    }

    public function valid(): bool
    {
        return $this->iterators->contains(
            static fn (array $stream): bool => $stream[0]->valid()
        );
    }

    public function next(): void
    {
        $this->iterators->first()[0]->next();

        $this->prioritizeIterators();
    }

    public function current(): ?DomainEvent
    {
        return $this->iterators->first()[0]->current();
    }

    public function streamName(): string
    {
        return $this->iterators->first()[1];
    }

    public function key(): StreamPosition
    {
        return $this->iterators->first()[0]->key();
    }

    /**
     * Returns the total number of events in all streams.
     * The count decreases as events are iterated over,
     * unless rewind() is called.
     *
     * @phpstan-impure
     */
    public function count(): int
    {
        return $this->iterators->sum(static fn (array $stream): int => $stream[0]->count());
    }

    private function prioritizeIterators(): void
    {
        if ($this->numberOfIterators > 1) {
            $this->iterators = $this->originalIteratorOrder
                ->filter(static fn (array $stream): bool => $stream[0]->valid())
                ->sortBy(fn (array $stream): DateTimeImmutable => $this->toDatetime($stream[0]->current()));
        }
    }

    private function toDatetime(DomainEvent $event): DateTimeImmutable
    {
        return $this->clock->from($event->header(Header::EVENT_TIME))->toDateTime();
    }
}
