<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\ProjectionRepository;
use Storm\Contract\Projector\ReadModel;
use Storm\Contract\Projector\ReadModelManagement;
use Storm\Projector\Workflow\Notification\Command\EventStreamDiscovered;
use Storm\Projector\Workflow\Notification\Command\SprintStopped;
use Storm\Projector\Workflow\Notification\Promise\CurrentStatus;

final readonly class ReadingModelManagement implements ReadModelManagement
{
    use InteractWithManagement;

    public function __construct(
        protected NotificationHub $hub,
        protected ProjectionRepository $projectionRepository,
        private ReadModel $readModel,
    ) {}

    public function rise(): void
    {
        $this->mountProjection();

        if (! $this->readModel->isInitialized()) {
            $this->readModel->initialize();
        }

        $this->hub->emit(EventStreamDiscovered::class);

        $this->synchronise();
    }

    public function store(): void
    {
        $this->projectionRepository->persist($this->takeSnapshot());

        $this->readModel->persist();
    }

    public function revise(): void
    {
        $this->resetSnapshot();

        $this->projectionRepository->reset(
            $this->takeSnapshot(),
            $this->hub->await(CurrentStatus::class)
        );

        $this->readModel->reset();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->projectionRepository->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->readModel->down();
        }

        $this->hub->emit(SprintStopped::class);

        $this->resetSnapshot();
    }

    public function getReadModel(): ReadModel
    {
        return $this->readModel;
    }
}
