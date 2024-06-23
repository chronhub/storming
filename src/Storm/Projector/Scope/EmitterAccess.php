<?php

declare(strict_types=1);

namespace Storm\Projector\Scope;

use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Projector\EmitterScope;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\Management\EventEmitted;
use Storm\Projector\Workflow\Notification\Management\EventLinkedTo;
use Storm\Projector\Workflow\Notification\Management\ProjectionClosed;
use Storm\Projector\Workflow\Notification\Stream\CurrentProcessedStream;

final readonly class EmitterAccess implements EmitterScope
{
    public function __construct(
        private NotificationHub $hub,
        private SystemClock $clock
    ) {
    }

    public function emit(DomainEvent $event): void
    {
        $this->hub->trigger(new EventEmitted($event));
    }

    public function linkTo(string $streamName, DomainEvent $event): void
    {
        $this->hub->trigger(new EventLinkedTo($streamName, $event));
    }

    public function stop(): void
    {
        $this->hub->trigger(new ProjectionClosed());
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
