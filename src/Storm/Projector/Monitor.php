<?php

declare(strict_types=1);

namespace Storm\Projector;

use Storm\Contract\Projector\ProjectionModel;
use Storm\Contract\Projector\ProjectionProvider;
use Storm\Contract\Projector\ProjectorMonitor;
use Storm\Contract\Serializer\SymfonySerializer;
use Storm\Projector\Exception\ProjectionFailed;
use Storm\Projector\Exception\ProjectionNotFound;
use Storm\Projector\Repository\Data\UpdateStatusData;
use Throwable;

final readonly class Monitor implements ProjectorMonitor
{
    public function __construct(
        private ProjectionProvider $projectionProvider,
        private SymfonySerializer $serializer,
    ) {}

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
        return $this->retrieveOrFail($projectionName)->status();
    }

    public function checkpointOf(string $projectionName): array
    {
        $projection = $this->retrieveOrFail($projectionName);

        return $this->serializer->decode($projection->checkpoint(), 'json');
    }

    public function stateOf(string $projectionName): array
    {
        $projection = $this->retrieveOrFail($projectionName);

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
     * Update the status of the projection.
     *
     * @throws ProjectionFailed   when the projection failed to update its status.
     * @throws ProjectionNotFound when the projection does not exist.
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
     * Retrieve the projection by name or fail if it does not exist.
     *
     * @throws ProjectionNotFound when the projection does not exist.
     */
    private function retrieveOrFail(string $projectionName): ProjectionModel
    {
        $projection = $this->projectionProvider->retrieve($projectionName);

        if (! $projection instanceof ProjectionModel) {
            throw ProjectionNotFound::withName($projectionName);
        }

        return $projection;
    }
}
