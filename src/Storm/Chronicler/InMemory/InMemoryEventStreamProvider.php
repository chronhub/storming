<?php

declare(strict_types=1);

namespace Storm\Chronicler\InMemory;

use Closure;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Stream\StreamName;

use function array_filter;
use function array_key_exists;
use function array_keys;
use function in_array;
use function is_null;
use function is_string;
use function str_starts_with;

final class InMemoryEventStreamProvider implements EventStreamProvider
{
    /** @var array{string, string|null}|array */
    private array $eventStreams;

    public function __construct()
    {
        $this->eventStreams = [];
    }

    public function createStream(string $streamName, ?string $streamTable, ?string $partition = null): bool
    {
        if (array_key_exists($streamName, $this->eventStreams)) {
            return false;
        }

        $this->eventStreams[$streamName] = $partition;

        return true;
    }

    public function deleteStream(string $streamName): bool
    {
        if (! array_key_exists($streamName, $this->eventStreams)) {
            return false;
        }

        unset($this->eventStreams[$streamName]);

        return true;
    }

    public function filterByStreams(array $streamNames): array
    {
        foreach ($streamNames as &$streamName) {
            if ($streamName instanceof StreamName) {
                $streamName = $streamName->name;
            }
        }

        return $this->pluckKeys(
            static fn (?string $partition, string $streamName) => is_null($partition) && in_array($streamName, $streamNames, true)
        );
    }

    public function filterByPartitions(array $partitions): array
    {
        return $this->pluckKeys(
            static fn (?string $partition) => is_string($partition) && in_array($partition, $partitions, true)
        );
    }

    public function all(): array
    {
        return $this->pluckKeys(
            static fn (?string $partition, string $streamName) => ! str_starts_with($streamName, StreamName::INTERNAL_PREFIX)
        );
    }

    public function hasRealStreamName(string $streamName): bool
    {
        return array_key_exists($streamName, $this->eventStreams);
    }

    private function pluckKeys(Closure $callback): array
    {
        return array_keys(
            array_filter(
                $this->eventStreams,
                $callback,
                ARRAY_FILTER_USE_BOTH
            )
        );
    }
}
