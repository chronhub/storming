<?php

declare(strict_types=1);

namespace Storm\Chronicler\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Storm\Chronicler\EventStream;
use Storm\Contract\Chronicler\EventStreamProvider;

final readonly class EventStreamDatabaseProvider implements EventStreamProvider
{
    final public const string TABLE_NAME = 'event_stream';

    public function __construct(
        private Connection $connection,
        private string $tableName = self::TABLE_NAME
    ) {}

    public function createStream(string $streamName, ?string $streamTable, ?string $partition = null): bool
    {
        $eventStream = new EventStream($streamName, $streamTable, $partition);

        return $this->query()->insert($eventStream->jsonSerialize());
    }

    public function deleteStream(string $streamName): bool
    {
        return $this->query()->where('real_stream_name', $streamName)->delete() === 1;
    }

    public function filterByStreams(array $streamNames): array
    {
        return $this->query()
            ->whereIn('real_stream_name', $streamNames)
            ->pluck('real_stream_name')
            ->toArray();
    }

    public function filterByPartitions(array $partitions): array
    {
        return $this->query()
            ->whereIn('partition', $partitions)
            ->pluck('real_stream_name')
            ->toArray();
    }

    public function all(): array
    {
        return $this->query()
            ->whereRaw("real_stream_name NOT LIKE '$%'")
            ->pluck('real_stream_name')
            ->toArray();
    }

    public function hasRealStreamName(string $streamName): bool
    {
        return $this->query()->where('real_stream_name', $streamName)->exists();
    }

    private function query(): Builder
    {
        return $this->connection->table($this->tableName);
    }
}
