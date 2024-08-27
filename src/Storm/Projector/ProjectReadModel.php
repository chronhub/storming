<?php

declare(strict_types=1);

namespace Storm\Projector;

use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\ReadModelProjector;
use Storm\Projector\Provider\Events\ProjectionClosed;
use Storm\Projector\Provider\Events\ProjectionDiscarded;
use Storm\Projector\Provider\Events\ProjectionRevised;
use Storm\Projector\Provider\Manager;
use Storm\Projector\Stream\Filter\ProjectionQueryFilter;
use Storm\Projector\Workflow\Process;

final readonly class ProjectReadModel implements ReadModelProjector
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
        ),
        );
    }

    public function reset(): void
    {
        $this->manager->call(fn (Process $process) => $process->dispatch(
            new ProjectionRevised
        )
        );
    }

    public function delete(bool $deleteEmittedEvents): void
    {
        $this->manager->call(
            fn (Process $process) => $process->dispatch(
                new ProjectionDiscarded($deleteEmittedEvents)
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
