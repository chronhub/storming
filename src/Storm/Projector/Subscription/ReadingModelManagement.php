<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\ProjectionRepository;
use Storm\Contract\Projector\ReadModel;
use Storm\Contract\Projector\ReadModelManagement;
use Storm\Contract\Projector\SnapshotRepository;
use Storm\Projector\Support\Notification\Sprint\SprintStopped;
use Storm\Projector\Support\Notification\Status\CurrentStatus;
use Storm\Projector\Support\Notification\Stream\EventStreamDiscovered;

final readonly class ReadingModelManagement implements ReadModelManagement
{
    use InteractWithManagement;

    public function __construct(
        protected NotificationHub $hub,
        protected ProjectionRepository $projectionRepository,
        protected SnapshotRepository $snapshotRepository,
        private ReadModel $readModel
    ) {
    }

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

        $this->projectionRepository->reset($this->getProjectionResult(), $this->hub->expect(CurrentStatus::class));

        $this->readModel->reset();

        $this->snapshotRepository->deleteByProjectionName($this->getName());
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->projectionRepository->delete($withEmittedEvents);

        $this->snapshotRepository->deleteByProjectionName($this->getName());

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
