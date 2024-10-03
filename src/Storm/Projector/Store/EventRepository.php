<?php

declare(strict_types=1);

namespace Storm\Projector\Store;

use Illuminate\Contracts\Events\Dispatcher;
use Storm\Contract\Projector\Repository;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Store\Events\ProjectionCreated;
use Storm\Projector\Store\Events\ProjectionDeleted;
use Storm\Projector\Store\Events\ProjectionDeletedWithEvents;
use Storm\Projector\Store\Events\ProjectionError;
use Storm\Projector\Store\Events\ProjectionReleased;
use Storm\Projector\Store\Events\ProjectionReset;
use Storm\Projector\Store\Events\ProjectionRestarted;
use Storm\Projector\Store\Events\ProjectionStarted;
use Storm\Projector\Store\Events\ProjectionStopped;
use Throwable;

final readonly class EventRepository implements Repository
{
    public function __construct(
        private Repository $repository,
        private Dispatcher $eventDispatcher
    ) {}

    public function create(ProjectionStatus $status): void
    {
        $this->dispatchWhen(
            fn () => $this->repository->create($status),
            ProjectionCreated::class,
            $status
        );
    }

    public function start(ProjectionStatus $status): void
    {
        $this->dispatchWhen(
            fn () => $this->repository->start($status),
            ProjectionStarted::class,
            $status
        );
    }

    public function stop(ProjectionSnapshot $snapshot, ProjectionStatus $status): void
    {
        $this->dispatchWhen(
            fn () => $this->repository->stop($snapshot, $status),
            ProjectionStopped::class,
            $status,
            $snapshot,
        );
    }

    public function startAgain(ProjectionStatus $status): void
    {
        $this->dispatchWhen(
            fn () => $this->repository->startAgain($status),
            ProjectionRestarted::class,
            $status
        );
    }

    public function reset(ProjectionSnapshot $snapshot, ProjectionStatus $status): void
    {
        $this->dispatchWhen(
            fn () => $this->repository->reset($snapshot, $status),
            ProjectionReset::class,
            $status,
            $snapshot
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

    public function persist(ProjectionSnapshot $snapshot): void
    {
        $this->repository->persist($snapshot);
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

    public function getName(): string
    {
        return $this->repository->getName();
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

            $this->eventDispatcher->dispatch(new $event($this->getName(), ...$arguments));
        } catch (Throwable $exception) {
            $errorEvent = new ProjectionError($this->getName(), $event, $exception);

            $this->eventDispatcher->dispatch($errorEvent);

            throw $exception;
        }
    }
}
