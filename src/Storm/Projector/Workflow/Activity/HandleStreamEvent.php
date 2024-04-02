<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Iterator\MergeStreamIterator;
use Storm\Projector\Workflow\Notification\Sprint\IsSprintRunning;
use Storm\Projector\Workflow\Notification\Stream\PullStreamIterator;
use Storm\Projector\Workflow\Notification\Stream\StreamProcessed;

use function gc_collect_cycles;

final class HandleStreamEvent
{
    /**
     * @var callable{NotificationHub, string, DomainEvent, positive-int}
     */
    private $eventProcessor;

    public function __construct(callable $eventProcessor)
    {
        $this->eventProcessor = $eventProcessor;
    }

    public function __invoke(NotificationHub $hub, callable $next): callable|bool
    {
        $streams = $hub->expect(PullStreamIterator::class);

        if (! $streams instanceof MergeStreamIterator) {
            return $next($hub);
        }

        foreach ($streams as $position => $event) {
            $streamName = $streams->streamName();

            $hub->notify(StreamProcessed::class, $streamName);

            $continue = ($this->eventProcessor)($hub, $streamName, $event, $position);

            if (! $continue || ! $hub->expect(IsSprintRunning::class)) {
                break;
            }
        }

        gc_collect_cycles();

        return $next($hub);
    }
}
