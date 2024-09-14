<?php

declare(strict_types=1);

namespace Storm\Chronicler\Database;

use Error;
use Exception;
use Generator;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use PDO;
use PDOException;
use Storm\Chronicler\Direction;
use Storm\Chronicler\Exceptions\ConfigurationViolation;
use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Contract\Aggregate\AggregateIdentity;
use Storm\Contract\Chronicler\DatabaseChronicler;
use Storm\Contract\Chronicler\DatabaseQueryLoader;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Chronicler\StreamPersistence;
use Storm\Stream\Stream;
use Storm\Stream\StreamName;

final readonly class PgsqlEventStore implements DatabaseChronicler
{
    final public const string DEFAULT_TABLE_NAME = 'stream_event';

    public function __construct(
        private Connection $connection,
        private EventStreamProvider $eventStreamProvider,
        private StreamPersistence $streamPersistence,
        private DatabaseQueryLoader $streamEventLoader,
        private string $streamTable = 'stream_event',
    ) {
        if ($this->connection->getDriverName() !== 'pgsql') {
            throw new ConfigurationViolation('Only pgsql driver is supported');
        }
    }

    public function append(Stream $stream): void
    {
        $streamEvents = $this->streamPersistence->normalize($stream);

        if ($streamEvents === []) {
            return;
        }

        try {
            $this->query()->useWritePdo()->insert($streamEvents);
        } catch (Exception $originalException) {

            // Depends on PDO attr error mode, which should be set to exception,
            // Check if the exception is a QueryException or PDOException
            $pdoException = $originalException instanceof PDOException
                ? $originalException
                : ($originalException->getPrevious() instanceof PDOException
                    ? $originalException->getPrevious()
                    : null);

            if ($pdoException === null) {
                throw $originalException;
            }

            // Handle the specific PDO exception codes
            match ($pdoException->getCode()) {
                '42P01' => throw StreamNotFound::withStreamName($stream->name, $pdoException),
                '23000', '23505', 23000, 23505 => throw new DatabaseConcurrencyFailure($pdoException->getMessage(), (int) $pdoException->getCode(), $pdoException),
                default => throw new DatabaseQueryFailure($pdoException->getMessage(), (int) $pdoException->getCode(), $pdoException)
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
            if ($exception->getCode() !== PDO::ERR_NONE) {
                throw new DatabaseQueryFailure($exception->getMessage(), (int) $exception->getCode(), $exception);
            }
        }

        try {
            $this->connection->getSchemaBuilder()->drop($streamName->name);
        } catch (QueryException $exception) {
            if ($exception->getCode() !== PDO::ERR_NONE) {
                throw new DatabaseQueryFailure($exception->getMessage(), (int) $exception->getCode(), $exception);
            }
        }
    }

    public function retrieveAll(StreamName $streamName, AggregateIdentity $aggregateId, Direction $direction = Direction::FORWARD): Generator
    {
        $query = $this->query()
            ->where('stream_name', $streamName->name)
            ->where('id', $aggregateId->toString())
            ->orderBy('position', $direction->value);

        return $this->streamEventLoader->load($query, $streamName);
    }

    public function retrieveFiltered(StreamName $streamName, QueryFilter $queryFilter): Generator
    {
        $query = $this->query()->where('stream_name', $streamName->name);

        $queryFilter->apply()($query);

        return $this->streamEventLoader->load($query, $streamName);
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

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function getEventStreamProvider(): EventStreamProvider
    {
        return $this->eventStreamProvider;
    }

    /**
     * Get the query builder for the main table.
     */
    private function query(): Builder
    {
        return $this->connection->table($this->streamTable);
    }
}
