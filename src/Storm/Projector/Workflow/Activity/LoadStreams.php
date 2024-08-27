<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Illuminate\Support\Collection;
use Storm\Contract\Clock\SystemClock;
use Storm\Projector\Stream\CollectStreams;
use Storm\Projector\Stream\Iterator\MergeStreamIterator;
use Storm\Projector\Workflow\Process;

final readonly class LoadStreams
{
    public function __construct(
        private CollectStreams $collectStreams,
        private SystemClock $clock,
    ) {}

    public function __invoke(Process $process): void
    {
        $checkpoints = $process->recognition()->toArray();

        $eventStreams = $this->collectStreams->fromCheckpoints($checkpoints);

        if ($eventStreams instanceof Collection) {
            $eventStreams = new MergeStreamIterator($this->clock, $eventStreams);
        }

        $process->batch()->set($eventStreams);
    }
}
