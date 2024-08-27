<?php

declare(strict_types=1);

namespace Storm\Chronicler\InMemory;

use Generator;
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
use Storm\Stream\Stream;
use Storm\Stream\StreamName;

/**
 * @phpstan-require-implements InMemoryChronicler
 */
trait ProvideInMemoryEventStore
{
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

    public function getEventStreamProvider(): EventStreamProvider
    {
        return $this->eventStreamProvider;
    }

    /**
     * @throws StreamNotFound
     */
    protected function assertStreamExists(StreamName $streamName): void
    {
        if (! $this->hasStream($streamName)) {
            throw StreamNotFound::withStreamName($streamName);
        }
    }

    /**
     * Store the stream events.
     */
    abstract protected function store(StreamName $streamName, iterable $events): void;

    /**
     * Filter the stream events.
     *
     * @return Generator<DomainEvent>
     *
     * @throws NoStreamEventReturn when no stream event is returned
     * @throws StreamNotFound      when the stream is not found
     */
    abstract protected function filter(StreamName $streamName, InMemoryQueryFilter $queryFilter): Generator;
}
