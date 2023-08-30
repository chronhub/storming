<?php

declare(strict_types=1);

namespace Storm\Contract\Chronicler;

interface EventStreamProvider
{
    public function createStream(string $streamName, ?string $streamTable, string $category = null): bool;

    public function deleteStream(string $streamName): bool;

    /**
     * @return array<string>
     */
    public function filterByAscendantStreams(array $streamNames): array;

    /**
     * @return array<string>
     */
    public function filterByAscendantCategories(array $categoryNames): array;

    /**
     * @return array<string>
     */
    public function allWithoutInternal(): array;

    public function hasRealStreamName(string $streamName): bool;
}
