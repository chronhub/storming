<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\ProjectionRepository;
use Storm\Contract\Projector\ReadModel;
use Storm\Contract\Projector\ReadModelManagement;
use Storm\Projector\Workflow\Notification\Sprint\SprintStopped;
use Storm\Projector\Workflow\Notification\Status\CurrentStatus;
use Storm\Projector\Workflow\Notification\Stream\EventStreamDiscovered;

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

        $this->hub->notify(EventStreamDiscovered::class);

        $this->synchronise();
    }

    public function store(): void
    {
        $this->projectionRepository->persist($this->getProjectionResult());

        $this->readModel->persist();
    }

    public function revise(): void
    {
        $this->resetState();

        $this->projectionRepository->reset(
            $this->getProjectionResult(),
            $this->hub->expect(CurrentStatus::class)
        );

        $this->readModel->reset();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->projectionRepository->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->readModel->down();
        }

        $this->hub->notify(SprintStopped::class);

        $this->resetState();
    }

    public function getReadModel(): ReadModel
    {
        return $this->readModel;
    }
}
