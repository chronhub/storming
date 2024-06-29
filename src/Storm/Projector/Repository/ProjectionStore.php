<?php

declare(strict_types=1);

namespace Storm\Projector\Repository;

use Storm\Contract\Projector\ProjectionModel;
use Storm\Contract\Projector\ProjectionProvider;
use Storm\Contract\Projector\ProjectionRepository;
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
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class ProjectionStore implements ProjectionRepository
{
    public function __construct(
        private ProjectionProvider $provider,
        private LockManager $lockManager,
        private SerializerInterface&EncoderInterface&DecoderInterface $serializer,
        private string $streamName
    ) {
    }

    public function create(ProjectionStatus $status): void
    {
        $data = new CreateData($status->value);

        $this->provider->createProjection($this->projectionName(), $data);
    }

    public function start(ProjectionStatus $projectionStatus): void
    {
        $data = new StartData($projectionStatus->value, $this->lockManager->acquire());

        $this->provider->acquireLock($this->projectionName(), $data);
    }

    public function stop(ProjectionResult $projectionResult, ProjectionStatus $projectionStatus): void
    {
        $data = new StopData(
            $projectionStatus->value,
            $this->serializer->serialize($projectionResult->userState, 'json'),
            $this->serializer->serialize($projectionResult->checkpoints, 'json'),
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

    public function persist(ProjectionResult $projectionResult): void
    {
        $data = new PersistData(
            $this->serializer->serialize($projectionResult->userState, 'json'),
            $this->serializer->serialize($projectionResult->checkpoints, 'json'),
            $this->lockManager->refresh()
        );

        $this->updateProjection($data);
    }

    public function reset(ProjectionResult $projectionResult, ProjectionStatus $currentStatus): void
    {
        $data = new ResetData(
            $currentStatus->value,
            $this->serializer->serialize($projectionResult->userState, 'json'),
            $this->serializer->serialize($projectionResult->checkpoints, 'json'),
        );

        $this->updateProjection($data);
    }

    public function delete(bool $withEmittedEvents): void
    {
        $this->provider->deleteProjection($this->streamName);
    }

    public function loadDetail(): ProjectionResult
    {
        $projection = $this->provider->retrieve($this->streamName);

        if (! $projection instanceof ProjectionModel) {
            throw ProjectionNotFound::withName($this->streamName);
        }

        return new ProjectionResult(
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
        $projection = $this->provider->retrieve($this->streamName);

        if (! $projection instanceof ProjectionModel) {
            return ProjectionStatus::RUNNING;
        }

        return ProjectionStatus::from($projection->status());
    }

    public function exists(): bool
    {
        return $this->provider->exists($this->streamName);
    }

    public function projectionName(): string
    {
        return $this->streamName;
    }

    private function updateProjection(ProjectionData $data): void
    {
        $this->provider->updateProjection($this->streamName, $data);
    }
}
