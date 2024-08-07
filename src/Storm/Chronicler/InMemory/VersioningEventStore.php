<?php

declare(strict_types=1);

namespace Storm\Chronicler\InMemory;

use Generator;
use Illuminate\Support\Collection;
use Storm\Chronicler\Direction;
use Storm\Chronicler\Exceptions\ConcurrencyException;
use Storm\Chronicler\Exceptions\NoStreamEventReturn;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Chronicler\InMemoryChronicler;
use Storm\Contract\Chronicler\InMemoryQueryFilter;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\EventHeader;
use Storm\Stream\Stream;
use Storm\Stream\StreamName;

use function array_diff;
use function array_map;
use function count;
use function iterator_to_array;

final class VersioningEventStore implements InMemoryChronicler
{
    use ProvideInMemoryEventStore;

    /** @var Collection<array<string, array<DomainEvent>>> */
    protected Collection $streams;

    public function __construct(
        protected readonly EventStreamProvider $eventStreamProvider,
    ) {
        $this->streams = new Collection();
    }

    /**
     * @return Collection<array<string, array<DomainEvent>>>
     */
    public function getStreams(): Collection
    {
        return $this->streams;
    }

    /**
     * @throws ConcurrencyException when the concurrency is detected
     */
    protected function store(StreamName $streamName, iterable $events): void
    {
        $streamEvents = $this->addInternalPositionIfNecessary(iterator_to_array($events));

        $this->assertNoConcurrency($streamName, $streamEvents);

        $this->streams = $this->streams->mergeRecursive([$streamName->name => $streamEvents]);
    }

    protected function filter(StreamName $streamName, InMemoryQueryFilter $queryFilter): Generator
    {
        $this->assertStreamExists($streamName);

        $events = collect($this->streams->get($streamName->name))
            ->sortBy(function (DomainEvent $event): int {
                return $event->header(EventHeader::INTERNAL_POSITION);
            }, SORT_NUMERIC, $queryFilter->orderBy() === Direction::BACKWARD)
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
                $aggregateVersion = $streamEvent->header(EventHeader::AGGREGATE_VERSION);
                $streamEvent = $streamEvent->withHeader($internalPosition, $aggregateVersion);
            }
        }

        return $streamEvents;
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
            throw new ConcurrencyException("In memory concurrency detected for stream $streamName->name");
        }
    }

    /**
     * Extract the aggregate version from the stream event.
     *
     * @return array<positive-int>
     */
    private function extractAggregateVersion(array $streamEvents): array
    {
        return array_map(
            fn (DomainEvent $event) => $event->header(EventHeader::AGGREGATE_VERSION),
            $streamEvents
        );
    }
}
