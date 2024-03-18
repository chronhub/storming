<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Illuminate\Support\Collection;
use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Checkpoint\Checkpoint;
use Storm\Projector\Iterator\MergeStreamIterator;
use Storm\Projector\Iterator\StreamIterator;
use Storm\Projector\Support\Notification\Checkpoint\CurrentCheckpoint;
use Storm\Projector\Support\Notification\Stream\StreamIteratorSet;
use Storm\Stream\StreamName;

final class LoadStreams
{
    /**
     * @var callable
     */
    private $queryFilterResolver;

    public function __construct(
        private readonly Chronicler $chronicler,
        private readonly SystemClock $clock,
        private readonly int $loadLimiter,
        callable $queryFilterResolver
    ) {
        $this->queryFilterResolver = $queryFilterResolver;
    }

    public function __invoke(NotificationHub $hub, callable $next): callable|bool
    {
        $checkpoints = $hub->expect(CurrentCheckpoint::class);

        $streams = $this->collectStreams($checkpoints);

        if ($streams) {
            $streams = new MergeStreamIterator($this->clock, $streams);
        }

        $hub->notify(StreamIteratorSet::class, $streams);

        return $next($hub);
    }

    /**
     * @param  array<string,Checkpoint>               $streams
     * @return null|Collection<string,StreamIterator>
     */
    private function collectStreams(array $streams): ?Collection
    {
        $streamEvents = new Collection();

        foreach ($streams as $streamName => $stream) {
            $queryFilter = ($this->queryFilterResolver)($streamName, $stream->position + 1, $this->loadLimiter);

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
