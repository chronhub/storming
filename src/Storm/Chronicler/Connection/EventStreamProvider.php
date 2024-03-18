<?php

declare(strict_types=1);

namespace Storm\Chronicler\Connection;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Storm\Chronicler\EventStream;
use Storm\Contract\Chronicler\EventStreamProvider as Provider;

final readonly class EventStreamProvider implements Provider
{
    public function __construct(
        protected Connection $connection,
        protected string $tableName = 'event_stream'
    ) {
    }

    public function createStream(string $streamName, ?string $streamTable, ?string $partition = null): bool
    {
        $eventStream = new EventStream($streamName, $streamTable, $partition);

        return $this->connect()->insert($eventStream->jsonSerialize());
    }

    public function deleteStream(string $streamName): bool
    {
        return $this->connect()->where('real_stream_name', $streamName)->delete() === 1;
    }

    public function filterByStreams(array $streamNames): array
    {
        return $this->connect()
            ->whereIn('real_stream_name', $streamNames)
            ->pluck('real_stream_name')
            ->toArray();
    }

    public function filterByCategories(array $categoryNames): array
    {
        return $this->connect()
            ->whereIn('category', $categoryNames)
            ->pluck('real_stream_name')
            ->toArray();
    }

    public function allWithoutInternal(): array
    {
        return $this->connect()
            ->whereRaw("real_stream_name NOT LIKE '$%'")
            ->pluck('real_stream_name')
            ->toArray();
    }

    public function hasRealStreamName(string $streamName): bool
    {
        return $this->connect()->where('real_stream_name', $streamName)->exists();
    }

    private function connect(): Builder
    {
        return $this->connection->table($this->tableName);
    }
}
