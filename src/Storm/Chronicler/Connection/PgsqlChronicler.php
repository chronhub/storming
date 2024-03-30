<?php

declare(strict_types=1);

namespace Storm\Chronicler\Connection;

use Generator;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Storm\Chronicler\Direction;
use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Contract\Aggregate\AggregateIdentity;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Chronicler\StreamEventConnectionLoader;
use Storm\Contract\Chronicler\StreamPersistence;
use Storm\Stream\Stream;
use Storm\Stream\StreamName;

final readonly class PgsqlChronicler implements Chronicler
{
    public function __construct(
        protected Connection $connection,
        protected EventStreamProvider $eventStreamProvider,
        protected StreamPersistence $streamPersistence,
        protected StreamEventConnectionLoader $streamEventLoader,
        protected string $masterTable = 'stream_event',
    ) {
    }

    public function append(Stream $stream): void
    {
        $streamEvents = $this->streamPersistence->normalize($stream);

        if ($streamEvents === []) {
            return;
        }

        try {
            $this->connection->table($this->masterTable)->useWritePdo()->insert($streamEvents);
        } catch (QueryException $exception) {
            match ($exception->getCode()) {
                '42P01' => throw StreamNotFound::withStreamName($stream->name, $exception),
                '23000', '23505' => throw new ConnectionConcurrencyFailure($exception->getMessage(), (int) $exception->getCode(), $exception),
                default => throw new ConnectionQueryFailure($exception->getMessage(), (int) $exception->getCode(), $exception)
            };
        }
    }

    public function delete(StreamName $streamName): void
    {
        try {
            $deleted = $this->eventStreamProvider->deleteStream($streamName->name);

            if (! $deleted) {
                throw StreamNotFound::withStreamName($streamName);
            }
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '00000') {
                throw new ConnectionQueryFailure($exception->getMessage(), (int) $exception->getCode(), $exception);
            }
        }

        try {
            $this->connection->getSchemaBuilder()->drop($streamName->name);
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '00000') {
                throw new ConnectionQueryFailure($exception->getMessage(), (int) $exception->getCode(), $exception);
            }
        }
    }

    public function retrieveAll(StreamName $streamName, AggregateIdentity $aggregateId, Direction $direction = Direction::FORWARD): Generator
    {
        $query = $this->connection->table($this->masterTable)
            ->where('stream_name', $streamName->name)
            ->where('id', $aggregateId->toString())

            ->orderBy('position', $direction->value);

        return $this->streamEventLoader->load($query, $streamName);
    }

    public function retrieveFiltered(StreamName $streamName, QueryFilter $queryFilter): Generator
    {
        $query = $this->connection->table($this->masterTable);
        $query->where('stream_name', $streamName->name);

        $queryFilter->apply()($query);

        return $this->streamEventLoader->load($query, $streamName);
    }

    public function filterStreams(string ...$streams): array
    {
        return $this->eventStreamProvider->filterByStreams($streams);
    }

    public function filterCategories(string ...$categories): array
    {
        return $this->eventStreamProvider->filterByCategories($categories);
    }

    public function hasStream(StreamName $streamName): bool
    {
        return $this->eventStreamProvider->hasRealStreamName($streamName->name);
    }
}
