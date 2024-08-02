<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Storm\Contract\Projector\ReadModel;
use Storm\Contract\Projector\ReadModelManagement;
use Storm\Contract\Projector\Repository;
use Storm\Projector\Workflow\Input\DiscoverEventStream;
use Storm\Projector\Workflow\Process;

final readonly class ReadingModelManagement implements ReadModelManagement
{
    use InteractWithManagement;

    public function __construct(
        protected Process $process,
        protected Repository $store,
        private ReadModel $readModel,
    ) {}

    public function rise(): void
    {
        $this->mountProjection();

        if (! $this->readModel->isInitialized()) {
            $this->readModel->initialize();
        }

        $this->process->call(new DiscoverEventStream());

        $this->synchronise();
    }

    public function store(): void
    {
        $snapshot = $this->takeSnapshot();

        $this->store->persist($snapshot);

        $this->readModel->persist();
    }

    public function revise(): void
    {
        $this->resetSnapshot();

        $this->store->reset(
            $this->takeSnapshot(),
            $this->process->status()->get()
        );

        $this->readModel->reset();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->store->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->readModel->down();
        }

        $this->process->sprint()->halt();

        $this->resetSnapshot();
    }
}
