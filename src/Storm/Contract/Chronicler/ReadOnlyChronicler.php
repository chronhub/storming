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
     * @throws StreamNotFound|NoStreamEventReturn
     */
    public function retrieveFiltered(StreamName $streamName, QueryFilter $queryFilter): Generator;

    public function filterStreams(string ...$streams): array;

    public function filterCategories(string ...$categories): array;

    public function hasStream(StreamName $streamName): bool;
}
