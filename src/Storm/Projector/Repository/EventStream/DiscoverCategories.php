<?php

declare(strict_types=1);

namespace Storm\Projector\Repository\EventStream;

use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Projector\Exception\InvalidArgumentException;

use function array_unique;
use function count;

final readonly class DiscoverCategories
{
    public function __construct(private array $categories)
    {
        $this->validateCategories();
    }

    public function __invoke(EventStreamProvider $provider): array
    {
        return $provider->filterByCategories($this->categories);
    }

    private function validateCategories(): void
    {
        if ($this->categories === []) {
            throw new InvalidArgumentException('Categories cannot be empty');
        }

        if (count($this->categories) !== count(array_unique($this->categories))) {
            throw new InvalidArgumentException('Categories cannot contain duplicate');
        }
    }
}
