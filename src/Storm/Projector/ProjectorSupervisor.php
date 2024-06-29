<?php

declare(strict_types=1);

namespace Storm\Projector;

use Storm\Contract\Projector\ProjectionModel;
use Storm\Contract\Projector\ProjectionProvider;
use Storm\Contract\Projector\ProjectorSupervisorInterface;
use Storm\Projector\Exception\ProjectionFailed;
use Storm\Projector\Exception\ProjectionNotFound;
use Storm\Projector\Repository\Data\UpdateStatusData;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

final readonly class ProjectorSupervisor implements ProjectorSupervisorInterface
{
    public function __construct(
        private ProjectionProvider $projectionProvider,
        private SerializerInterface&EncoderInterface&DecoderInterface $serializer,
    ) {
    }

    public function markAsStop(string $projectionName): void
    {
        $this->applyStatus($projectionName, ProjectionStatus::STOPPING);
    }

    public function markAsReset(string $projectionName): void
    {
        $this->applyStatus($projectionName, ProjectionStatus::RESETTING);
    }

    public function markAsDelete(string $projectionName, bool $withEmittedEvents): void
    {
        $deleteProjectionStatus = $withEmittedEvents
            ? ProjectionStatus::DELETING_WITH_EMITTED_EVENTS
            : ProjectionStatus::DELETING;

        $this->applyStatus($projectionName, $deleteProjectionStatus);
    }

    public function statusOf(string $projectionName): string
    {
        return $this->tryRetrieveProjectionByName($projectionName)->status();
    }

    public function checkpointOf(string $projectionName): array
    {
        $projection = $this->tryRetrieveProjectionByName($projectionName);

        return $this->serializer->decode($projection->checkpoint(), 'json');
    }

    public function stateOf(string $projectionName): array
    {
        $projection = $this->tryRetrieveProjectionByName($projectionName);

        return $this->serializer->decode($projection->state(), 'json');
    }

    public function filterNames(string ...$streamNames): array
    {
        return $this->projectionProvider->filterByNames(...$streamNames);
    }

    public function exists(string $projectionName): bool
    {
        return $this->projectionProvider->exists($projectionName);
    }

    /**
     * @throws ProjectionFailed
     * @throws ProjectionNotFound
     */
    private function applyStatus(string $projectionName, ProjectionStatus $projectionStatus): void
    {
        try {
            $data = new UpdateStatusData($projectionStatus->value);

            $this->projectionProvider->updateProjection($projectionName, $data);
        } catch (Throwable $exception) {
            if ($exception instanceof ProjectionFailed || $exception instanceof ProjectionNotFound) {
                throw $exception;
            }

            throw new ProjectionFailed($exception->getMessage(), (int) $exception->getCode(), $exception);
        }
    }

    /**
     * @throws ProjectionNotFound
     */
    private function tryRetrieveProjectionByName(string $projectionName): ProjectionModel
    {
        $projection = $this->projectionProvider->retrieve($projectionName);

        if (! $projection instanceof ProjectionModel) {
            throw ProjectionNotFound::withName($projectionName);
        }

        return $projection;
    }
}
