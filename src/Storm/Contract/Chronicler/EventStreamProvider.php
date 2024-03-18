<?php

declare(strict_types=1);

namespace Storm\Contract\Chronicler;

interface EventStreamProvider
{
    public function createStream(string $streamName, ?string $streamTable, ?string $partition = null): bool;

    public function deleteStream(string $streamName): bool;

    /**
     * @return array<string>
     */
    public function filterByStreams(array $streamNames): array;

    /**
     * @return array<string>
     */
    public function filterByCategories(array $categoryNames): array;

    /**
     * @return array<string>
     */
    public function allWithoutInternal(): array;

    public function hasRealStreamName(string $streamName): bool;
}
