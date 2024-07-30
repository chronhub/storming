<?php

declare(strict_types=1);

namespace Storm\Projector\Scope;

use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Projector\EmitterScope;
use Storm\Projector\Workflow\Notification\Management\ProjectionClosed;
use Storm\Projector\Workflow\Notification\Management\StreamEventEmitted;
use Storm\Projector\Workflow\Notification\Management\StreamEventLinkedTo;
use Storm\Projector\Workflow\WorkflowContext;

final readonly class EmitterAccess implements EmitterScope
{
    public function __construct(
        private WorkflowContext $workflowContext,
        private SystemClock $clock
    ) {}

    public function emit(DomainEvent $event): void
    {
        $this->workflowContext->emit(new StreamEventEmitted($event));
    }

    public function linkTo(string $streamName, DomainEvent $event): void
    {
        $this->workflowContext->emit(new StreamEventLinkedTo($streamName, $event));
    }

    public function stop(): void
    {
        $this->workflowContext->emit(new ProjectionClosed());
    }

    public function streamName(): string
    {
        return $this->workflowContext->processedStream()->get();
    }

    public function clock(): SystemClock
    {
        return $this->clock;
    }
}
