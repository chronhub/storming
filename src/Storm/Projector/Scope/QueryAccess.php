<?php

declare(strict_types=1);

namespace Storm\Projector\Scope;

use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\QueryProjectorScope;
use Storm\Projector\Workflow\Notification\Sprint\SprintStopped;
use Storm\Projector\Workflow\Notification\Stream\CurrentProcessedStream;

final readonly class QueryAccess implements QueryProjectorScope
{
    public function __construct(
        private NotificationHub $hub,
        private SystemClock $clock
    ) {
    }

    public function stop(): void
    {
        $this->hub->notify(SprintStopped::class);
    }

    public function streamName(): string
    {
        return $this->hub->expect(CurrentProcessedStream::class);
    }

    public function clock(): SystemClock
    {
        return $this->clock;
    }
}
