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

// todo: bind all data to events
final readonly class EventDispatcherRepository implements ProjectionRepository
{
    public function __construct(
        private ProjectionRepository $repository,
        private Dispatcher $eventDispatcher
    ) {}

    public function create(ProjectionStatus $projectionStatus): void
    {
        $this->dispatchWhen(
            fn () => $this->repository->create($projectionStatus),
            ProjectionCreated::class
        );
    }

    public function start(ProjectionStatus $projectionStatus): void
    {
        $this->dispatchWhen(
            fn () => $this->repository->start($projectionStatus),
            ProjectionStarted::class
        );
    }

    public function stop(ProjectionSnapshot $projectionSnapshot, ProjectionStatus $projectionStatus): void
    {
        $this->dispatchWhen(
            fn () => $this->repository->stop($projectionSnapshot, $projectionStatus),
            ProjectionStopped::class,
        );
    }

    public function startAgain(ProjectionStatus $projectionStatus): void
    {
        $this->dispatchWhen(
            fn () => $this->repository->startAgain($projectionStatus),
            ProjectionRestarted::class
        );
    }

    public function reset(ProjectionSnapshot $projectionSnapshot, ProjectionStatus $currentStatus): void
    {
        $this->dispatchWhen(
            fn () => $this->repository->reset($projectionSnapshot, $currentStatus),
            ProjectionReset::class,
            $projectionSnapshot
        );
    }

    public function delete(bool $withEmittedEvents): void
    {
        $event = $withEmittedEvents ? ProjectionDeletedWithEvents::class : ProjectionDeleted::class;

        $this->dispatchWhen(
            fn () => $this->repository->delete($withEmittedEvents),
            $event
        );
    }

    public function release(): void
    {
        $this->dispatchWhen(
            fn () => $this->repository->release(),
            ProjectionReleased::class
        );
    }

    public function persist(ProjectionSnapshot $projectionSnapshot): void
    {
        $this->repository->persist($projectionSnapshot);
    }

    public function updateLock(): void
    {
        $this->repository->updateLock();
    }

    public function loadStatus(): ProjectionStatus
    {
        return $this->repository->loadStatus();
    }

    public function loadSnapshot(): ProjectionSnapshot
    {
        return $this->repository->loadSnapshot();
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
     * Dispatch the event only if the operation is successful.
     * Otherwise, dispatch the error event.
     *
     * @param class-string $event
     *
     * @throws Throwable
     */
    private function dispatchWhen(callable $operation, string $event, mixed ...$arguments): void
    {
        try {
            $operation();

            $this->eventDispatcher->dispatch(new $event($this->projectionName(), ...$arguments));
        } catch (Throwable $exception) {
            $errorEvent = new ProjectionError($this->projectionName(), $event, $exception);

            $this->eventDispatcher->dispatch($errorEvent);

            throw $exception;
        }
    }
}
