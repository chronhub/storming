<?php

declare(strict_types=1);

namespace Storm\Contract\Chronicler;

use Generator;
use Storm\Chronicler\Direction;
use Storm\Chronicler\Exceptions\NoStreamEventReturn;
use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Contract\Aggregate\AggregateIdentity;
use Storm\Contract\Message\DomainEvent;
use Storm\Stream\StreamName;

interface ReadOnlyChronicler
{
    /**
     * Retrieve all events for the given stream.
     *
     * @return Generator{DomainEvent}
     *
     * @throws StreamNotFound      when the stream does not exist
     * @throws NoStreamEventReturn when no events are returned
     */
    public function retrieveAll(StreamName $streamName, AggregateIdentity $aggregateId, Direction $direction = Direction::FORWARD): Generator;

    /**
     * Retrieve events for the given stream using the given query filter.
     *
     * @return Generator{DomainEvent}
     *
     * @throws StreamNotFound      When the stream does not exist
     * @throws NoStreamEventReturn When no events are returned
     */
    public function retrieveFiltered(StreamName $streamName, QueryFilter $queryFilter): Generator;

    /**
     * Retrieve filtered streams.
     */
    public function filterStreams(string ...$streams): array;

    /**
     * Retrieve filtered partitions.
     */
    public function filterPartitions(string ...$partitions): array;

    /**
     * Check if the stream exists.
     */
    public function hasStream(StreamName $streamName): bool;
}
