<?php

declare(strict_types=1);

namespace Storm\Projector\Stream\Query;

use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Projector\Exception\InvalidArgumentException;

use function array_unique;
use function count;

final readonly class DiscoverStream
{
    public function __construct(public array $streams)
    {
        $this->validateStreams();
    }

    /**
     * @return array|array<string>
     */
    public function __invoke(EventStreamProvider $provider): array
    {
        return $provider->filterByStreams($this->streams);
    }

    private function validateStreams(): void
    {
        if ($this->streams === []) {
            throw new InvalidArgumentException('Streams cannot be empty');
        }

        if (count($this->streams) !== count(array_unique($this->streams))) {
            throw new InvalidArgumentException('Streams cannot contain duplicate');
        }
    }
}
