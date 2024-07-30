<?php

declare(strict_types=1);

namespace Storm\Projector\Repository;

use Storm\Contract\Projector\ProjectionModel;
use Storm\Contract\Projector\ProjectionProvider;
use Storm\Contract\Projector\ProjectionRepository;
use Storm\Contract\Serializer\SymfonySerializer;
use Storm\Projector\Exception\ProjectionNotFound;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Repository\Data\CreateData;
use Storm\Projector\Repository\Data\PersistData;
use Storm\Projector\Repository\Data\ProjectionData;
use Storm\Projector\Repository\Data\ReleaseData;
use Storm\Projector\Repository\Data\ResetData;
use Storm\Projector\Repository\Data\StartAgainData;
use Storm\Projector\Repository\Data\StartData;
use Storm\Projector\Repository\Data\StopData;
use Storm\Projector\Repository\Data\UpdateLockData;

final readonly class ProjectionRepositoryStore implements ProjectionRepository
{
    public function __construct(
        private ProjectionProvider $provider,
        private LockManager $lockManager,
        private SymfonySerializer $serializer,
        private string $name
    ) {}

    public function create(ProjectionStatus $projectionStatus): void
    {
        $data = new CreateData($projectionStatus->value);

        $this->provider->createProjection($this->name, $data);
    }

    public function start(ProjectionStatus $projectionStatus): void
    {
        $data = new StartData($projectionStatus->value, $this->lockManager->acquire());

        $this->provider->acquireLock($this->name, $data);
    }

    public function stop(ProjectionSnapshot $projectionSnapshot, ProjectionStatus $projectionStatus): void
    {
        $data = new StopData(
            $projectionStatus->value,
            $this->serializer->serialize($projectionSnapshot->userState, 'json'),
            $this->serializer->serialize($projectionSnapshot->checkpoints, 'json'),
            $this->lockManager->refresh()
        );

        $this->updateProjection($data);
    }

    public function release(): void
    {
        $data = new ReleaseData(ProjectionStatus::IDLE->value, null);

        $this->updateProjection($data);
    }

    public function startAgain(ProjectionStatus $projectionStatus): void
    {
        $data = new StartAgainData($projectionStatus->value, $this->lockManager->acquire());

        $this->updateProjection($data);
    }

    public function persist(ProjectionSnapshot $projectionSnapshot): void
    {
        $data = new PersistData(
            $this->serializer->serialize($projectionSnapshot->userState, 'json'),
            $this->serializer->serialize($projectionSnapshot->checkpoints, 'json'),
            $this->lockManager->refresh()
        );

        $this->updateProjection($data);
    }

    public function reset(ProjectionSnapshot $projectionSnapshot, ProjectionStatus $currentStatus): void
    {
        $data = new ResetData(
            $currentStatus->value,
            $this->serializer->serialize($projectionSnapshot->userState, 'json'),
            $this->serializer->serialize($projectionSnapshot->checkpoints, 'json'),
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
