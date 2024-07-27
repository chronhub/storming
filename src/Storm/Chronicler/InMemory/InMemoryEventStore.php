<?php

declare(strict_types=1);

namespace Storm\Chronicler\InMemory;

use Generator;
use Illuminate\Support\Collection;
use Storm\Chronicler\Direction;
use Storm\Chronicler\Exceptions\ConcurrencyException;
use Storm\Chronicler\Exceptions\InvalidArgumentException;
use Storm\Chronicler\Exceptions\NoStreamEventReturn;
use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Contract\Aggregate\AggregateIdentity;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Chronicler\InMemoryChronicler;
use Storm\Contract\Chronicler\InMemoryQueryFilter;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\EventHeader;
use Storm\Stream\Stream;
use Storm\Stream\StreamName;

use function array_diff;
use function array_map;
use function count;
use function iterator_to_array;

final class InMemoryEventStore implements InMemoryChronicler
{
    /** @var Collection<array<string, array<DomainEvent>>> */
    private Collection $streams;

    public function __construct(
        private readonly EventStreamProvider $eventStreamProvider,
    ) {
        $this->streams = new Collection();
    }

    public function append(Stream $stream): void
    {
        $streamName = $stream->name;

        $this->eventStreamProvider->createStream($streamName->name, null, $streamName->partition());

        $this->store($streamName, $stream->events());
    }

    public function delete(StreamName $streamName): void
    {
        $this->assertStreamExists($streamName);

        $this->eventStreamProvider->deleteStream($streamName->name);

        $this->streams = $this->streams->forget($streamName->name);
    }

    public function retrieveAll(StreamName $streamName, AggregateIdentity $aggregateId, Direction $direction = Direction::FORWARD): Generator
    {
        $queryFilter = new RetrieveAllInMemoryQueryFilter($aggregateId, $direction);

        return $this->retrieveFiltered($streamName, $queryFilter);
    }

    public function retrieveFiltered(StreamName $streamName, QueryFilter $queryFilter): Generator
    {
        if (! $queryFilter instanceof InMemoryQueryFilter) {
            throw new InvalidArgumentException('Query filter must be an instance of InMemoryQueryFilter');
        }

        $this->assertStreamExists($streamName);

        return $this->filter($streamName, $queryFilter);
    }

    public function filterStreams(string ...$streams): array
    {
        return $this->eventStreamProvider->filterByStreams($streams);
    }

    public function filterPartitions(string ...$partitions): array
    {
        return $this->eventStreamProvider->filterByPartitions($partitions);
    }

    public function hasStream(StreamName $streamName): bool
    {
        return $this->eventStreamProvider->hasRealStreamName($streamName->name);
    }

    public function getStreams(): Collection
    {
        return $this->streams;
    }

    private function store(StreamName $streamName, iterable $events): void
    {
        $streamEvents = $this->addInternalPositionIfNecessary(iterator_to_array($events));

        $this->assertNoConcurrency($streamName, $streamEvents);

        $this->streams = $this->streams->mergeRecursive([$streamName->name => $streamEvents]);
    }

    private function filter(StreamName $streamName, InMemoryQueryFilter $queryFilter): Generator
    {
        $this->assertStreamExists($streamName);

        $events = collect($this->streams->get($streamName->name))
            ->sortBy(fn (DomainEvent $event): int => $event->header(EventHeader::INTERNAL_POSITION), SORT_NUMERIC, $queryFilter->orderBy() === Direction::BACKWARD)
            ->filter($queryFilter->apply())
            ->whenEmpty(fn () => throw NoStreamEventReturn::withStreamName($streamName));

        yield from $events;

        return count($events);
    }

    private function addInternalPositionIfNecessary(array $streamEvents): array
    {
        foreach ($streamEvents as &$streamEvent) {
            $internalPosition = EventHeader::INTERNAL_POSITION;

            if ($streamEvent->hasNot($internalPosition)) {
                $streamEvent = $streamEvent->withHeader(
                    $internalPosition,
                    $streamEvent->header(EventHeader::AGGREGATE_VERSION)
                );
            }
        }

        return $streamEvents;
    }

    private function assertStreamExists(StreamName $streamName): void
    {
        if (! $this->hasStream($streamName)) {
            throw StreamNotFound::withStreamName($streamName);
        }
    }

    /**
     * Simulates the concurrency detection.
     *
     * @throws ConcurrencyException
     */
    private function assertNoConcurrency(StreamName $streamName, iterable $streamEvents): void
    {
        $currentStream = $this->streams->get($streamName->name);

        if (! $currentStream) {
            return;
        }

        $positions = $this->extractAggregateVersion($currentStream);

        $streamEvents = iterator_to_array($streamEvents);
        $nextPositions = $this->extractAggregateVersion($streamEvents);

        if (array_diff($nextPositions, $positions) === []) {
            throw new ConcurrencyException(
                "In memory concurrency detected for stream $streamName->name"
            );
        }
    }

    /**
     * Extract the aggregate version from the stream event.
     *
     * @return array<int<0, max>>
     */
    private function extractAggregateVersion(array $streamEvents): array
    {
        return array_map(
            fn (DomainEvent $event) => $event->header(EventHeader::AGGREGATE_VERSION),
            $streamEvents
        );
    }
}
