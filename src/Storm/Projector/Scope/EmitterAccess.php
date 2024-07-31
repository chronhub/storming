<?php

declare(strict_types=1);

namespace Storm\Projector\Scope;

use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Projector\EmitterScope;
use Storm\Projector\Workflow\Management\ProjectionClosed;
use Storm\Projector\Workflow\Management\StreamEventEmitted;
use Storm\Projector\Workflow\Management\StreamEventLinkedTo;
use Storm\Projector\Workflow\Process;

final readonly class EmitterAccess implements EmitterScope
{
    public function __construct(
        private Process $process,
        private SystemClock $clock
    ) {}

    public function emit(DomainEvent $event): void
    {
        $this->process->dispatch(new StreamEventEmitted($event));
    }

    public function linkTo(string $streamName, DomainEvent $event): void
    {
        $this->process->dispatch(new StreamEventLinkedTo($streamName, $event));
    }

    public function stop(): void
    {
        $this->process->dispatch(new ProjectionClosed());
    }

    public function streamName(): string
    {
        return $this->process->stream()->get();
    }

    public function clock(): SystemClock
    {
        return $this->clock;
    }
}
