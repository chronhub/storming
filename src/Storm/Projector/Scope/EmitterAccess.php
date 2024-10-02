<?php

declare(strict_types=1);

namespace Storm\Projector\Scope;

use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Message\DomainEvent;
use Storm\Projector\Projection\Events\ProjectionClosed;
use Storm\Projector\Projection\Events\StreamEventEmitted;
use Storm\Projector\Projection\Events\StreamEventLinkedTo;
use Storm\Projector\Workflow\Process;

final class EmitterAccess implements EmitterScope
{
    use ScopeAccess;

    public function __construct(
        protected readonly Process $process,
        protected readonly SystemClock $clock,
        public ?UserState $userState = null,
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
        $this->process->dispatch(new ProjectionClosed);
    }
}
