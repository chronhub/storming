<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Contract\Message\DomainEvent;
use Storm\Projector\Stream\Iterator\MergeStreamIterator;
use Storm\Projector\Workflow\Process;
use Storm\Stream\StreamPosition;

use function gc_collect_cycles;

final class HandleStreamEvent
{
    /** @var callable(Process, string, DomainEvent, StreamPosition): bool */
    private $eventReactor;

    public function __construct(callable $eventReactor)
    {
        $this->eventReactor = $eventReactor;
    }

    public function __invoke(Process $process): void
    {
        $streams = $process->batch()->pull();

        if (! $streams instanceof MergeStreamIterator) {
            return;
        }

        while ($streams->valid()) {
            $process->stream()->set($streams->streamName());

            $continue = ($this->eventReactor)($process, $streams->streamName(), $streams->current(), $streams->key());

            if (! $continue || ! $process->sprint()->inProgress()) {
                break;
            }

            $streams->next();
        }

        gc_collect_cycles();
    }
}
