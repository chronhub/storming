<?php

declare(strict_types=1);

namespace Storm\Contract\Chronicler;

interface EventStreamProvider
{
    /**
     * Create a new stream.
     */
    public function createStream(string $streamName, ?string $streamTable, ?string $partition = null): bool;

    /**
     * Delete a stream.
     */
    public function deleteStream(string $streamName): bool;

    /**
     * Filter by stream names.
     *
     * @return array<string>
     */
    public function filterByStreams(array $streamNames): array;

    /**
     * Filter by partitions.
     *
     * @return array<string>
     */
    public function filterByPartitions(array $partitions): array;

    /**
     * Fetch all stream names without internal stream names.
     *
     * @see StreamName::INTERNAL_PREFIX
     *
     * @return array<string>
     */
    public function allWithoutInternal(): array;

    /**
     * Check if a stream exists.
     */
    public function hasRealStreamName(string $streamName): bool;
}
