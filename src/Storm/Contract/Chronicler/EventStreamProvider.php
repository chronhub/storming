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
     * @return array<string>|array
     */
    public function filterByStreams(array $streamNames): array;

    /**
     * Filter by partitions.
     *
     * @return array<string>|array
     */
    public function filterByPartitions(array $partitions): array;

    /**
     * Fetch all stream names.
     *
     * Internally, it filters out streams with stream name prefix.
     * checkMe with pgsql standard strategy, it should not be used.
     *
     * @see StreamName::INTERNAL_PREFIX
     *
     * @return array<string>|array
     */
    public function all(): array;

    /**
     * Check if a stream exists.
     */
    public function hasRealStreamName(string $streamName): bool;
}
