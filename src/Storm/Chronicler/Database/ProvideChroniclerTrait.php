<?php

declare(strict_types=1);

namespace Storm\Chronicler\Database;

use Generator;
use Storm\Chronicler\Direction;
use Storm\Contract\Aggregate\AggregateIdentity;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Stream\Stream;
use Storm\Stream\StreamName;

/**
 * @phpstan-require-implements Chronicler
 */
trait ProvideChroniclerTrait
{
    public function append(Stream $stream): void
    {
        $this->chronicler->append($stream);
    }

    public function delete(StreamName $streamName): void
    {
        $this->chronicler->delete($streamName);
    }

    public function retrieveAll(StreamName $streamName, AggregateIdentity $aggregateId, Direction $direction = Direction::FORWARD): Generator
    {
        return $this->chronicler->retrieveAll($streamName, $aggregateId, $direction);
    }

    public function retrieveFiltered(StreamName $streamName, QueryFilter $queryFilter): Generator
    {
        return $this->chronicler->retrieveFiltered($streamName, $queryFilter);
    }

    public function filterStreams(string ...$streams): array
    {
        return $this->chronicler->filterStreams(...$streams);
    }

    public function filterPartitions(string ...$partitions): array
    {
        return $this->chronicler->filterPartitions(...$partitions);
    }

    public function hasStream(StreamName $streamName): bool
    {
        return $this->chronicler->hasStream($streamName);
    }

    public function getEventStreamProvider(): EventStreamProvider
    {
        return $this->chronicler->getEventStreamProvider();
    }
}
