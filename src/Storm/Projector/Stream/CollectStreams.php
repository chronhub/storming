<?php

declare(strict_types=1);

namespace Storm\Projector\Stream;

use Illuminate\Support\Collection;
use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Projector\Checkpoint\Checkpoint;
use Storm\Projector\Stream\Filter\LoadLimiter;
use Storm\Projector\Stream\Iterator\StreamIterator;
use Storm\Stream\StreamName;
use Storm\Stream\StreamPosition;

class CollectStreams
{
    /** @var callable(string, StreamPosition, LoadLimiter): QueryFilter */
    private $queryFilterResolver;

    public function __construct(
        private readonly Chronicler $chronicler,
        private readonly LoadLimiter $loadLimiter,
        callable $queryFilterResolver
    ) {
        $this->queryFilterResolver = $queryFilterResolver;
    }

    /**
     * Collects the stream events from the given checkpoints.
     *
     * @param  array<string, Checkpoint>               $checkpoints
     * @return null|Collection<string, StreamIterator>
     */
    public function fromCheckpoints(array $checkpoints): ?Collection
    {
        $streamEvents = new Collection;

        foreach ($checkpoints as $checkpoint) {
            $streamName = $checkpoint->streamName;
            $streamPosition = new StreamPosition($checkpoint->position + 1);
            $queryFilter = ($this->queryFilterResolver)($streamName, $streamPosition, $this->loadLimiter);

            try {
                $events = $this->chronicler->retrieveFiltered(new StreamName($streamName), $queryFilter);

                $streamEvents->push([new StreamIterator($events), $streamName]);
            } catch (StreamNotFound) {
                continue;
            }
        }

        return $streamEvents->isEmpty() ? null : $streamEvents;
    }
}
