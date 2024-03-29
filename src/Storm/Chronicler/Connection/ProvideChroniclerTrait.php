<?php

declare(strict_types=1);

namespace Storm\Chronicler\Connection;

use Generator;
use Storm\Chronicler\Direction;
use Storm\Contract\Aggregate\AggregateIdentity;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Stream\Stream;
use Storm\Stream\StreamName;

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

    public function filterCategories(string ...$categories): array
    {
        return $this->chronicler->filterCategories(...$categories);
    }

    public function hasStream(StreamName $streamName): bool
    {
        return $this->chronicler->hasStream($streamName);
    }
}
