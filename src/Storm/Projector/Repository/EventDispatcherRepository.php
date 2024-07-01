<?php

declare(strict_types=1);

namespace Storm\Projector\Repository;

use Illuminate\Contracts\Events\Dispatcher;
use Storm\Contract\Projector\ProjectionRepository;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Repository\Events\ProjectionCreated;
use Storm\Projector\Repository\Events\ProjectionDeleted;
use Storm\Projector\Repository\Events\ProjectionDeletedWithEvents;
use Storm\Projector\Repository\Events\ProjectionError;
use Storm\Projector\Repository\Events\ProjectionReleased;
use Storm\Projector\Repository\Events\ProjectionReset;
use Storm\Projector\Repository\Events\ProjectionRestarted;
use Storm\Projector\Repository\Events\ProjectionStarted;
use Storm\Projector\Repository\Events\ProjectionStopped;
use Throwable;

final readonly class EventDispatcherRepository implements ProjectionRepository
{
    public function __construct(
        private ProjectionRepository $repository,
        private Dispatcher $eventDispatcher
    ) {}

    public function create(ProjectionStatus $status): void
    {
        $this->dispatchWhen(
            fn () => $this->repository->create($status),
            fn () => $this->eventDispatcher->dispatch(new ProjectionCreated($this->projectionName())),
            ProjectionCreated::class
        );
    }

    public function start(ProjectionStatus $projectionStatus): void
    {
        $this->dispatchWhen(
            fn () => $this->repository->start($projectionStatus),
            fn () => $this->eventDispatcher->dispatch(new ProjectionStarted($this->projectionName())),
            ProjectionStarted::class
        );
    }

    public function stop(ProjectionResult $projectionResult, ProjectionStatus $projectionStatus): void
    {
        $this->dispatchWhen(
            fn () => $this->repository->stop($projectionResult, $projectionStatus),
            fn () => $this->eventDispatcher->dispatch(new ProjectionStopped($this->projectionName())),
            ProjectionStopped::class
        );
    }

    public function startAgain(ProjectionStatus $projectionStatus): void
    {
        $this->dispatchWhen(
            fn () => $this->repository->startAgain($projectionStatus),
            fn () => $this->eventDispatcher->dispatch(new ProjectionRestarted($this->projectionName())),
            ProjectionRestarted::class
        );
    }

    public function reset(ProjectionResult $projectionResult, ProjectionStatus $currentStatus): void
    {
        $this->dispatchWhen(
            fn () => $this->repository->reset($projectionResult, $currentStatus),
            fn () => $this->eventDispatcher->dispatch(new ProjectionReset($this->projectionName(), $projectionResult)),
            ProjectionReset::class
        );
    }

    public function delete(bool $withEmittedEvents): void
    {
        $event = $withEmittedEvents ? ProjectionDeletedWithEvents::class : ProjectionDeleted::class;

        $this->dispatchWhen(
            fn () => $this->repository->delete($withEmittedEvents),
            fn () => $this->eventDispatcher->dispatch(new $event($this->projectionName())),
            $event
        );
    }

    public function release(): void
    {
        $this->dispatchWhen(
            fn () => $this->repository->release(),
            fn () => $this->eventDispatcher->dispatch(new ProjectionReleased($this->projectionName())),
            ProjectionReleased::class
        );
    }

    public function persist(ProjectionResult $projectionResult): void
    {
        $this->repository->persist($projectionResult);
    }

    public function updateLock(): void
    {
        $this->repository->updateLock();
    }

    public function loadStatus(): ProjectionStatus
    {
        return $this->repository->loadStatus();
    }

    public function loadDetail(): ProjectionResult
    {
        return $this->repository->loadDetail();
    }

    public function exists(): bool
    {
        return $this->repository->exists();
    }

    public function projectionName(): string
    {
        return $this->repository->projectionName();
    }

    /**
     * @param class-string $failedEvent
     *
     * @throws Throwable
     */
    private function dispatchWhen(callable $operation, callable $onSuccess, string $failedEvent): void
    {
        try {
            $operation();

            $onSuccess();
        } catch (Throwable $exception) {
            $errorEvent = new ProjectionError($this->projectionName(), $failedEvent, $exception);

            $this->eventDispatcher->dispatch($errorEvent);

            throw $exception;
        }
    }
}
