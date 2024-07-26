<?php

declare(strict_types=1);

namespace Storm\Projector\Scope;

use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Projector\EmitterScope;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\Management\ProjectionClosed;
use Storm\Projector\Workflow\Notification\Management\StreamEventEmitted;
use Storm\Projector\Workflow\Notification\Management\StreamEventLinkedTo;
use Storm\Projector\Workflow\Notification\Promise\CurrentProcessedStream;

final readonly class EmitterAccess implements EmitterScope
{
    public function __construct(
        private NotificationHub $hub,
        private SystemClock $clock
    ) {}

    public function emit(DomainEvent $event): void
    {
        $this->hub->emit(new StreamEventEmitted($event));
    }

    public function linkTo(string $streamName, DomainEvent $event): void
    {
        $this->hub->emit(new StreamEventLinkedTo($streamName, $event));
    }

    public function stop(): void
    {
        $this->hub->emit(new ProjectionClosed());
    }

    public function streamName(): string
    {
        return $this->hub->await(CurrentProcessedStream::class);
    }

    public function clock(): SystemClock
    {
        return $this->clock;
    }
}
