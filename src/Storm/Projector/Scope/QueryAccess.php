<?php

declare(strict_types=1);

namespace Storm\Projector\Scope;

use ArrayAccess;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\QueryProjectorScope;
use Storm\Projector\Support\Notification\Sprint\SprintStopped;
use Storm\Projector\Support\Notification\Stream\CurrentProcessedStream;

final class QueryAccess implements ArrayAccess, QueryProjectorScope
{
    use ScopeBehaviour;

    public function __construct(
        private readonly NotificationHub $hub,
        private readonly SystemClock $clock
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
