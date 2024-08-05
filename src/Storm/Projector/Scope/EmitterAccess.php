<?php

declare(strict_types=1);

namespace Storm\Projector\Scope;

use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Message\DomainEvent;
use Storm\Projector\Workflow\Management\ProjectionClosed;
use Storm\Projector\Workflow\Management\StreamEventEmitted;
use Storm\Projector\Workflow\Management\StreamEventLinkedTo;
use Storm\Projector\Workflow\Process;

final class EmitterAccess implements EmitterScope
{
    use BoundScope;

    public function __construct(
        protected readonly Process $process,
        protected readonly SystemClock $clock,
        public ?UserStateScope $userState = null,
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
}
