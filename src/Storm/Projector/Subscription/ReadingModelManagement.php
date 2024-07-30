<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Storm\Contract\Projector\ProjectionRepository;
use Storm\Contract\Projector\ReadModel;
use Storm\Contract\Projector\ReadModelManagement;
use Storm\Projector\Workflow\WorkflowContext;

final readonly class ReadingModelManagement implements ReadModelManagement
{
    use InteractWithManagement;

    public function __construct(
        protected WorkflowContext $workflowContext,
        protected ProjectionRepository $projectionRepository,
        private ReadModel $readModel,
    ) {}

    public function rise(): void
    {
        $this->mountProjection();

        if (! $this->readModel->isInitialized()) {
            $this->readModel->initialize();
        }

        $this->workflowContext->discoverEventStream();

        $this->synchronise();
    }

    public function store(): void
    {
        $snapshot = $this->workflowContext->takeSnapshot();

        $this->projectionRepository->persist($snapshot);

        $this->readModel->persist();
    }

    public function revise(): void
    {
        $this->workflowContext->resetSnapshot();

        $this->projectionRepository->reset(
            $this->workflowContext->takeSnapshot(),
            $this->workflowContext->status()->get()
        );

        $this->readModel->reset();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->projectionRepository->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->readModel->down();
        }

        $this->workflowContext->sprint()->halt();

        $this->workflowContext->resetSnapshot();
    }
}
