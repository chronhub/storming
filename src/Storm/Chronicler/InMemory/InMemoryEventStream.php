<?php

declare(strict_types=1);

namespace Storm\Chronicler\InMemory;

use Closure;
use Illuminate\Support\Collection;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Stream\StreamName;

use function in_array;
use function is_null;
use function is_string;
use function str_starts_with;

final readonly class InMemoryEventStream implements EventStreamProvider
{
    /**
     * @var Collection{string, string|null}
     */
    private Collection $eventStreams;

    public function __construct()
    {
        $this->eventStreams = new Collection();
    }

    public function createStream(string $streamName, ?string $streamTable, string $category = null): bool
    {
        if ($this->eventStreams->has($streamName)) {
            return false;
        }

        $this->eventStreams->put($streamName, $category);

        return true;
    }

    public function deleteStream(string $streamName): bool
    {
        if (! $this->eventStreams->has($streamName)) {
            return false;
        }

        $this->eventStreams->forget($streamName);

        return true;
    }

    public function filterByAscendantStreams(array $streamNames): array
    {
        foreach ($streamNames as &$streamName) {
            if ($streamName instanceof StreamName) {
                $streamName = $streamName->name;
            }
        }

        return $this->sortAndPluckKeys(
            static fn (?string $category, string $streamName) => is_null($category) && in_array($streamName, $streamNames, true)
        );
    }

    public function filterByAscendantCategories(array $categoryNames): array
    {
        return $this->sortAndPluckKeys(
            static fn (?string $category) => is_string($category) && in_array($category, $categoryNames, true)
        );
    }

    public function allWithoutInternal(): array
    {
        return $this->sortAndPluckKeys(
            static fn (?string $category, string $streamName) => ! str_starts_with($streamName, '$')
        );
    }

    public function hasRealStreamName(string $streamName): bool
    {
        return $this->eventStreams->has($streamName);
    }

    private function sortAndPluckKeys(Closure $callback): array
    {
        return $this->eventStreams->filter($callback)->sortKeys()->keys()->toArray();
    }
}
