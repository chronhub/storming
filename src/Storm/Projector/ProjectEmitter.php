<?php

declare(strict_types=1);

namespace Storm\Projector;

use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\EmitterProjector;
use Storm\Projector\Projection\Events\ProjectionClosed;
use Storm\Projector\Projection\Events\ProjectionDiscarded;
use Storm\Projector\Projection\Events\ProjectionRevised;
use Storm\Projector\Projection\Manager;
use Storm\Projector\Stream\Filter\ProjectionQueryFilter;
use Storm\Projector\Workflow\Process;

final readonly class ProjectEmitter implements EmitterProjector
{
    use InteractWithProjection;

    public function __construct(
        protected Manager $manager,
        protected ContextReader $context,
        protected string $streamName
    ) {}

    public function run(bool $inBackground): void
    {
        $this->describeIfNeeded();

        $this->manager->start($this->context, $inBackground);
    }

    public function stop(): void
    {
        $this->manager->call(fn (Process $process) => $process->dispatch(
            new ProjectionClosed,
        ));
    }

    public function reset(): void
    {
        $this->manager->call(fn (Process $process) => $process->dispatch(
            new ProjectionRevised,
        ));
    }

    public function delete(bool $deleteEmittedEvents): void
    {
        $this->manager->call(fn (Process $process) => $process->dispatch(
            new ProjectionDiscarded($deleteEmittedEvents),
        ));
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
