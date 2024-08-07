<?php

declare(strict_types=1);

namespace Storm\Chronicler\InMemory;

use Generator;
use Illuminate\Support\Collection;
use Storm\Chronicler\Direction;
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

use function count;
use function iterator_to_array;

final class InMemoryAutoIncrementEventStore implements InMemoryChronicler
{
    /** @var Collection<array<string, array<positive-int, DomainEvent>>> */
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
        $position = count($this->streams->get($streamName->name, [])) + 1;

        $streamEvents = $this->addInternalPositionIfNecessary($position, iterator_to_array($events));

        $this->streams = $this->streams->mergeRecursive([$streamName->name => $streamEvents]);
    }

    private function filter(StreamName $streamName, InMemoryQueryFilter $queryFilter): Generator
    {
        $this->assertStreamExists($streamName);

        $events = collect($this->streams->get($streamName->name))
            ->sortBy(function (array $streamEvent): int {
                return $streamEvent[0];
            }, SORT_NUMERIC, $queryFilter->orderBy() === Direction::BACKWARD)
            ->filter($queryFilter->apply())
            ->map(function (array $streamEvent): DomainEvent {
                return $streamEvent[1];
            })
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
                $streamEvent = $streamEvent->withHeader(
                    $internalPosition,
                    $position
                );
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
