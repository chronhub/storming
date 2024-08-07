<?php

declare(strict_types=1);

namespace Storm\Projector;

use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\EmitterProjector;
use Storm\Projector\Provider\Events\ProjectionDiscarded;
use Storm\Projector\Provider\Events\ProjectionRevised;
use Storm\Projector\Provider\Subscriptor;
use Storm\Projector\Stream\Filter\ProjectionQueryFilter;
use Storm\Projector\Workflow\Process;

final readonly class ProjectEmitter implements EmitterProjector
{
    use InteractWithProjection;

    public function __construct(
        protected Subscriptor $subscriber,
        protected ContextReader $context,
        protected string $streamName
    ) {}

    public function run(bool $inBackground): void
    {
        $this->describeIfNeeded();

        $this->subscriber->start($this->context, $inBackground);
    }

    public function reset(): void
    {
        $this->subscriber->call(
            fn (Process $process) => $process->dispatch(
                new ProjectionRevised(),
            ),
        );
    }

    public function delete(bool $deleteEmittedEvents): void
    {
        $this->subscriber->call(
            fn (Process $process) => $process->dispatch(
                new ProjectionDiscarded($deleteEmittedEvents),
            )
        );
    }

    public function filter(ProjectionQueryFilter $queryFilter): static
    {
        $this->context->withQueryFilter($queryFilter);

        return $this;
    }

    public function getName(): string
    {
        return $this->streamName;
    }
}
