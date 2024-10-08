<?php

declare(strict_types=1);

namespace Storm\Projector\Store;

use Storm\Contract\Projector\ProjectionModel;
use Storm\Contract\Projector\ProjectionProvider;
use Storm\Contract\Projector\Repository;
use Storm\Contract\Serializer\SymfonySerializer;
use Storm\Projector\Exception\ProjectionNotFound;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Store\Data\CreateData;
use Storm\Projector\Store\Data\PersistData;
use Storm\Projector\Store\Data\ProjectionData;
use Storm\Projector\Store\Data\ReleaseData;
use Storm\Projector\Store\Data\ResetData;
use Storm\Projector\Store\Data\StartAgainData;
use Storm\Projector\Store\Data\StartData;
use Storm\Projector\Store\Data\StopData;
use Storm\Projector\Store\Data\UpdateLockData;

final readonly class ProjectionRepository implements Repository
{
    public function __construct(
        private ProjectionProvider $provider,
        private LockManager $lockManager,
        private SymfonySerializer $serializer,
        private string $name
    ) {}

    public function create(ProjectionStatus $status): void
    {
        $data = new CreateData($status->value);

        $this->provider->createProjection($this->name, $data);
    }

    public function start(ProjectionStatus $status): void
    {
        $data = new StartData($status->value, $this->lockManager->acquire());

        $this->provider->acquireLock($this->name, $data);
    }

    public function stop(ProjectionSnapshot $snapshot, ProjectionStatus $status): void
    {
        $data = new StopData(
            $status->value,
            $this->serializer->serialize($snapshot->userState, 'json'),
            $this->serializer->serialize($snapshot->checkpoint, 'json'),
            $this->lockManager->refresh()
        );

        $this->updateProjection($data);
    }

    public function release(): void
    {
        $data = new ReleaseData(ProjectionStatus::IDLE->value, null);

        $this->updateProjection($data);
    }

    public function startAgain(ProjectionStatus $status): void
    {
        $data = new StartAgainData($status->value, $this->lockManager->acquire());

        $this->updateProjection($data);
    }

    public function persist(ProjectionSnapshot $snapshot): void
    {
        $data = new PersistData(
            $this->serializer->serialize($snapshot->userState, 'json'),
            $this->serializer->serialize($snapshot->checkpoint, 'json'),
            $this->lockManager->refresh()
        );

        $this->updateProjection($data);
    }

    public function reset(ProjectionSnapshot $snapshot, ProjectionStatus $status): void
    {
        $data = new ResetData(
            $status->value,
            $this->serializer->serialize($snapshot->userState, 'json'),
            $this->serializer->serialize($snapshot->checkpoint, 'json'),
        );

        $this->updateProjection($data);
    }

    public function delete(bool $withEmittedEvents): void
    {
        $this->provider->deleteProjection($this->name);
    }

    public function loadSnapshot(): ProjectionSnapshot
    {
        $projection = $this->provider->retrieve($this->name);

        if (! $projection instanceof ProjectionModel) {
            throw ProjectionNotFound::withName($this->name);
        }

        return new ProjectionSnapshot(
            $this->serializer->decode($projection->checkpoint(), 'json'),
            $this->serializer->decode($projection->state(), 'json'),
        );
    }

    public function updateLock(): void
    {
        if ($this->lockManager->shouldRefresh()) {
            $data = new UpdateLockData($this->lockManager->refresh());

            $this->updateProjection($data);
        }
    }

    public function loadStatus(): ProjectionStatus
    {
        $projection = $this->provider->retrieve($this->name);

        if (! $projection instanceof ProjectionModel) {
            return ProjectionStatus::RUNNING;
        }

        return ProjectionStatus::from($projection->status());
    }

    public function exists(): bool
    {
        return $this->provider->exists($this->name);
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Update the projection with the given data.
     */
    private function updateProjection(ProjectionData $data): void
    {
        $this->provider->updateProjection($this->name, $data);
    }
}
