<?php

declare(strict_types=1);

namespace Storm\Projector\Stream\Query;

use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Projector\Exception\InvalidArgumentException;

use function array_unique;
use function count;

final readonly class DiscoverPartition
{
    public function __construct(public array $partitions)
    {
        $this->validatePartitions();
    }

    /**
     * @return array|array<string>
     */
    public function __invoke(EventStreamProvider $provider): array
    {
        return $provider->filterByPartitions($this->partitions);
    }

    private function validatePartitions(): void
    {
        if ($this->partitions === []) {
            throw new InvalidArgumentException('Partition cannot be empty');
        }

        if (count($this->partitions) !== count(array_unique($this->partitions))) {
            throw new InvalidArgumentException('Partition cannot contain duplicate');
        }
    }
}
