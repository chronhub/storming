<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Illuminate\Support\Collection;
use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Checkpoint\Checkpoint;
use Storm\Projector\Filter\LoadLimiter;
use Storm\Projector\Iterator\MergeStreamIterator;
use Storm\Projector\Iterator\StreamIterator;
use Storm\Projector\Workflow\Notification\Command\BatchStreamSet;
use Storm\Projector\Workflow\Notification\Promise\CurrentCheckpoint;
use Storm\Stream\StreamName;
use Storm\Stream\StreamPosition;

final class LoadStreams
{
    /** @var callable(string, StreamPosition, LoadLimiter): QueryFilter */
    private $queryFilterResolver;

    public function __construct(
        private readonly Chronicler $chronicler,
        private readonly SystemClock $clock,
        private readonly LoadLimiter $loadLimiter,
        callable $queryFilterResolver
    ) {
        $this->queryFilterResolver = $queryFilterResolver;
    }

    public function __invoke(NotificationHub $hub): bool
    {
        $checkpoints = $hub->await(CurrentCheckpoint::class);

        $streams = $this->collectStreams($checkpoints);

        if ($streams instanceof Collection) {
            $streams = new MergeStreamIterator($this->clock, $streams);
        }

        $hub->emit(BatchStreamSet::class, $streams);

        return true;
    }

    /**
     * Collects the stream events from the given checkpoints.
     *
     * @param  array<Checkpoint>                       $checkpoints
     * @return null|Collection<string, StreamIterator>
     */
    private function collectStreams(array $checkpoints): ?Collection
    {
        $streamEvents = new Collection();

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
