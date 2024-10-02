<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Repository;

use Closure;
use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery\MockInterface;
use RuntimeException;
use Storm\Contract\Projector\Repository;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Storage\EventRepository;
use Storm\Projector\Storage\Events\ProjectionCreated;
use Storm\Projector\Storage\Events\ProjectionDeleted;
use Storm\Projector\Storage\Events\ProjectionDeletedWithEvents;
use Storm\Projector\Storage\Events\ProjectionError;
use Storm\Projector\Storage\Events\ProjectionReleased;
use Storm\Projector\Storage\Events\ProjectionReset;
use Storm\Projector\Storage\Events\ProjectionRestarted;
use Storm\Projector\Storage\Events\ProjectionStarted;
use Storm\Projector\Storage\Events\ProjectionStopped;
use Storm\Tests\Stubs\ProjectionSnapshotStub;
use Throwable;

beforeEach(function () {
    $this->projectionName = 'projection-name';
    $this->repository = mock(Repository::class);
    $this->eventDispatcher = mock(Dispatcher::class);
    $this->eventDispatcherRepository = new EventRepository($this->repository, $this->eventDispatcher);
    $this->projectionResultStub = new ProjectionSnapshotStub;
});

dataset('exceptions', [new Exception('error message'), new RuntimeException('error message')]);

function assertErrorEventDispatched(string $projectionName, string $expectedEvent, Throwable $exception): Closure
{
    return function (Dispatcher|MockInterface $dispatcher) use ($projectionName, $expectedEvent, $exception) {
        $dispatcher->expects('dispatch')->withArgs(
            fn (ProjectionError $event) => $event->projectionName === $projectionName
                && $event->event === $expectedEvent
                && $event->error === $exception);
    };
}

test('dispatch event when create projection', function (ProjectionStatus $status) {
    $this->repository->expects('getName')->andReturn($this->projectionName);
    $this->repository->expects('create')->with($status);

    $this->eventDispatcher->expects('dispatch')->withArgs(
        fn (ProjectionCreated $event) => $event->name === $this->projectionName
    );

    $this->eventDispatcherRepository->create($status);
})->with('projection status');

test('dispatch error event when create projection raise exception', function (Throwable $exception) {
    $status = ProjectionStatus::RUNNING;
    $this->repository->expects('getName')->andReturn($this->projectionName);
    $this->repository->expects('create')->with($status)->andThrow($exception);

    assertErrorEventDispatched($this->projectionName, ProjectionCreated::class, $exception)($this->eventDispatcher);

    try {
        $this->eventDispatcherRepository->create($status);
    } catch (Throwable $e) {
        expect($e)->toBe($exception);
    }
})->with('exceptions');

test('dispatch event when start projection', function (ProjectionStatus $status) {
    $this->repository->expects('getName')->andReturn($this->projectionName)->once();
    $this->repository->expects('start')->with($status)->once();

    $this->eventDispatcher->expects('dispatch')->withArgs(
        fn (ProjectionStarted $event) => $event->name === $this->projectionName
    );

    $this->eventDispatcherRepository->start($status);
})->with('projection status');

test('dispatch error event when start projection raise exception', function (Throwable $exception) {
    $status = ProjectionStatus::RUNNING;
    $this->repository->expects('getName')->andReturn($this->projectionName);
    $this->repository->expects('start')->with($status)->andThrow($exception);

    assertErrorEventDispatched($this->projectionName, ProjectionStarted::class, $exception)($this->eventDispatcher);

    try {
        $this->eventDispatcherRepository->start($status);
    } catch (Throwable $e) {
        expect($e)->toBe($exception);
    }
})->with('exceptions');

test('dispatch event when stop projection', function (ProjectionStatus $status) {
    $result = $this->projectionResultStub->fromDefault();

    $this->repository->expects('getName')->andReturn($this->projectionName);
    $this->repository->expects('stop')->with($result, $status);

    $this->eventDispatcher
        ->shouldReceive('dispatch')
        ->withArgs(fn (ProjectionStopped $event) => $event->name === $this->projectionName)
        ->once();

    $this->eventDispatcherRepository->stop($result, $status);
})->with('projection status');

test('dispatch error event when stop projection raise exception', function (Throwable $exception) {
    $status = ProjectionStatus::RUNNING;
    $result = $this->projectionResultStub->fromDefault();

    $this->repository->expects('getName')->andReturn($this->projectionName);
    $this->repository->expects('stop')->with($result, $status)->andThrow($exception);

    assertErrorEventDispatched($this->projectionName, ProjectionStopped::class, $exception)($this->eventDispatcher);

    try {
        $this->eventDispatcherRepository->stop($result, $status);
    } catch (Throwable $e) {
        expect($e)->toBe($exception);
    }
})->with('exceptions');

test('dispatch event when start again projection', function (ProjectionStatus $status) {
    $this->repository->expects('getName')->andReturn($this->projectionName);
    $this->repository->expects('startAgain')->with($status);

    $this->eventDispatcher
        ->expects('dispatch')
        ->withArgs(fn (ProjectionRestarted $event) => $event->name === $this->projectionName);

    $this->eventDispatcherRepository->startAgain($status);
})->with('projection status');

test('dispatch event when reset projection', function (ProjectionStatus $status) {
    $result = $this->projectionResultStub->fromDefault();

    $this->repository->expects('getName')->andReturn($this->projectionName);
    $this->repository->expects('reset')->with($result, $status);

    $this->eventDispatcher->expects('dispatch')->withArgs(
        function (ProjectionReset $event) use ($result) {
            return $event->name === $this->projectionName
                && $event->snapshot === $result;
        });

    $this->eventDispatcherRepository->reset($result, $status);
})->with('projection status');

test('dispatch error event when reset projection raise exception', function (Throwable $exception) {
    $status = ProjectionStatus::RUNNING;
    $result = $this->projectionResultStub->fromDefault();

    $this->repository->expects('getName')->andReturn($this->projectionName);
    $this->repository->expects('reset')->with($result, $status)->andThrow($exception);

    assertErrorEventDispatched($this->projectionName, ProjectionReset::class, $exception)($this->eventDispatcher);

    try {
        $this->eventDispatcherRepository->reset($result, $status);
    } catch (Throwable $e) {
        expect($e)->toBe($exception);
    }
})->with('exceptions');

test('dispatch event when delete projection', function (bool $withEmittedEvents) {
    $this->repository->expects('getName')->andReturn($this->projectionName);
    $this->repository->expects('delete')->with($withEmittedEvents);

    $expectedEvent = $withEmittedEvents ? ProjectionDeletedWithEvents::class : ProjectionDeleted::class;

    $this->eventDispatcher->expects('dispatch')
        ->withArgs(function (object $event) use ($expectedEvent) {
            return $event::class === $expectedEvent && $event->projectionName === $this->projectionName;
        });

    $this->eventDispatcherRepository->delete($withEmittedEvents);
})->with('delete projection with emitted events');

test('dispatch error event when delete projection without emitted events raise exception', function (Throwable $exception) {
    $this->repository->expects('getName')->andReturn($this->projectionName);
    $this->repository->expects('delete')->with(false)->andThrow($exception);

    assertErrorEventDispatched($this->projectionName, ProjectionDeleted::class, $exception)($this->eventDispatcher);

    try {
        $this->eventDispatcherRepository->delete(false);
    } catch (Throwable $e) {
        expect($e)->toBe($exception);
    }
})->with('exceptions');

test('dispatch error event when delete projection with emitted events raise exception', function (Throwable $exception) {
    $this->repository->expects('getName')->andReturn($this->projectionName);
    $this->repository->expects('delete')->with(true)->andThrow($exception);

    assertErrorEventDispatched($this->projectionName, ProjectionDeletedWithEvents::class, $exception)($this->eventDispatcher);

    try {
        $this->eventDispatcherRepository->delete(true);
    } catch (Throwable $e) {
        expect($e)->toBe($exception);
    }
})->with('exceptions');

test('dispatch event when release projection', function () {
    $this->repository->expects('getName')->andReturn($this->projectionName);
    $this->repository->expects('release');

    $this->eventDispatcher->expects('dispatch')->withArgs(
        fn (ProjectionReleased $event) => $event->projectionName === $this->projectionName
    );

    $this->eventDispatcherRepository->release();
});

test('persist projection result', function () {
    $result = $this->projectionResultStub->fromDefault();

    $this->repository->expects('persist')->with($result);

    $this->eventDispatcherRepository->persist($result);
});

test('update lock', function () {
    $this->repository->expects('updateLock');

    $this->eventDispatcherRepository->updateLock();
});

test('load status', function (ProjectionStatus $status) {
    $this->repository->expects('loadStatus')->andReturn($status);

    expect($this->eventDispatcherRepository->loadStatus())->toBe($status);
})->with('projection status');

test('load detail', function () {
    $result = $this->projectionResultStub->fromDefault();

    $this->repository->expects('loadSnapshot')->andReturn($result);

    expect($this->eventDispatcherRepository->loadSnapshot())->toBe($result);
});

test('exists', function (bool $exists) {
    $this->repository->expects('exists')->andReturn($exists);

    expect($this->eventDispatcherRepository->exists())->toBe($exists);
})->with('projection exists');
