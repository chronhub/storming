<?php

declare(strict_types=1);

namespace Storm\Projector\Projection;

use Storm\Contract\Projector\ReadModel;
use Storm\Contract\Projector\Repository;
use Storm\Projector\Workflow\Input\DiscoverEventStream;
use Storm\Projector\Workflow\Process;

final readonly class ReadingModelProjection implements ReadModelProjection
{
    use InteractWithProvider;

    public function __construct(
        protected Process $process,
        protected Repository $repository,
        private ReadModel $readModel,
    ) {}

    public function rise(): void
    {
        $this->mountProjection();

        if (! $this->readModel->isInitialized()) {
            $this->readModel->initialize();
        }

        $this->process->call(new DiscoverEventStream);

        $this->synchronise();
    }

    public function store(): void
    {
        $snapshot = $this->takeSnapshot();

        $this->repository->persist($snapshot);

        $this->readModel->persist();
    }

    public function revise(): void
    {
        $this->resetSnapshot();

        $this->repository->reset(
            $this->takeSnapshot(),
            $this->process->status()->get()
        );

        $this->readModel->reset();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->repository->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->readModel->down();
        }

        $this->process->sprint()->halt();

        $this->resetSnapshot();
    }
}
