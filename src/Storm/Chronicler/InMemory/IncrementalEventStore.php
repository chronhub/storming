<?php

declare(strict_types=1);

namespace Storm\Chronicler\InMemory;

use Generator;
use Illuminate\Support\Collection;
use Storm\Chronicler\Direction;
use Storm\Chronicler\Exceptions\NoStreamEventReturn;
use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Chronicler\InMemoryChronicler;
use Storm\Contract\Chronicler\InMemoryQueryFilter;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\EventHeader;
use Storm\Stream\StreamName;

use function count;
use function iterator_to_array;

final class IncrementalEventStore implements InMemoryChronicler
{
    use ProvideInMemoryEventStore;

    /** @var Collection<array<string, array<positive-int, DomainEvent>>> */
    protected Collection $streams;

    public function __construct(
        protected readonly EventStreamProvider $eventStreamProvider,
    ) {
        $this->streams = new Collection();
    }

    /**
     * @internal
     *
     * @return Collection<array<string, array<positive-int, DomainEvent>>>
     */
    public function getStreams(): Collection
    {
        return $this->streams;
    }

    protected function store(StreamName $streamName, iterable $events): void
    {
        $position = count($this->streams->get($streamName->name, [])) + 1;

        $streamEvents = $this->addInternalPositionIfNecessary($position, iterator_to_array($events));

        $this->streams = $this->streams->mergeRecursive([$streamName->name => $streamEvents]);
    }

    protected function filter(StreamName $streamName, InMemoryQueryFilter $queryFilter): Generator
    {
        $this->assertStreamExists($streamName);

        $events = collect($this->streams->get($streamName->name))
            ->sortBy(fn (array $streamEvent): int => $streamEvent[0], SORT_NUMERIC, $queryFilter->orderBy() === Direction::BACKWARD)
            ->map(fn (array $streamEvent): DomainEvent => $streamEvent[1])
            ->filter($queryFilter->apply())
            ->whenEmpty(fn () => throw NoStreamEventReturn::withStreamName($streamName));

        yield from $events;

        return count($events);
    }

    private function addInternalPositionIfNecessary(int $position, array $streamEvents): array
    {
        $result = [];

        foreach ($streamEvents as $streamEvent) {
            $internalPosition = EventHeader::INTERNAL_POSITION;

            if ($streamEvent->hasNot($internalPosition)) {
                $streamEvent = $streamEvent->withHeader($internalPosition, $position);
            }

            $result[] = [$position, $streamEvent];
            $position++;
        }

        return $result;
    }

    private function assertStreamExists(StreamName $streamName): void
    {
        if (! $this->hasStream($streamName)) {
            throw StreamNotFound::withStreamName($streamName);
        }
    }
}
