<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Iterator\MergeStreamIterator;
use Storm\Projector\Workflow\Notification\Command\StreamProcessed;
use Storm\Projector\Workflow\Notification\Promise\IsSprintRunning;
use Storm\Projector\Workflow\Notification\Promise\PullBatchStream;
use Storm\Stream\StreamPosition;

use function gc_collect_cycles;

final class HandleStreamEvent
{
    /** @var callable{NotificationHub, string, DomainEvent, StreamPosition} */
    private $eventProcessor;

    public function __construct(callable $eventProcessor)
    {
        $this->eventProcessor = $eventProcessor;
    }

    public function __invoke(NotificationHub $hub, callable $next): callable|bool
    {
        $streams = $hub->await(PullBatchStream::class);

        if (! $streams instanceof MergeStreamIterator) {
            return $next($hub);
        }

        while ($streams->valid()) {
            $streamName = $streams->streamName();
            $hub->emit(StreamProcessed::class, $streamName);

            $continue = ($this->eventProcessor)($hub, $streamName, $streams->current(), $streams->key());

            if (! $continue || ! $hub->await(IsSprintRunning::class)) {
                break;
            }

            $streams->next();
        }

        gc_collect_cycles();

        return $next($hub);
    }
}
