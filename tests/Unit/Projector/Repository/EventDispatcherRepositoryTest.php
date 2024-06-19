<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Repository;

use Closure;
use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use RuntimeException;
use Storm\Contract\Projector\ProjectionRepository;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Repository\EventDispatcherRepository;
use Storm\Projector\Repository\Events\ProjectionCreated;
use Storm\Projector\Repository\Events\ProjectionDeleted;
use Storm\Projector\Repository\Events\ProjectionDeletedWithEvents;
use Storm\Projector\Repository\Events\ProjectionError;
use Storm\Projector\Repository\Events\ProjectionReleased;
use Storm\Projector\Repository\Events\ProjectionReset;
use Storm\Projector\Repository\Events\ProjectionRestarted;
use Storm\Projector\Repository\Events\ProjectionStarted;
use Storm\Projector\Repository\Events\ProjectionStopped;
use Storm\Projector\Repository\ProjectionResult;
use Throwable;

beforeEach(function () {
    $this->repository = $this->createMock(ProjectionRepository::class);
    $this->eventDispatcher = $this->createMock(Dispatcher::class);
    $this->eventDispatcherRepository = new EventDispatcherRepository($this->repository, $this->eventDispatcher);
});

dataset('boolean', [true, false]);
dataset('exceptions', [new Exception('error message'), new RuntimeException('error message')]);

function assertErrorEventDispatched(string $projectionName, string $expectedEvent, Throwable $exception): Closure
{
    return function ($test) use ($projectionName, $expectedEvent, $exception) {
        $test->eventDispatcher->expects($test::once())->method('dispatch')->with(
            $test->callback(function ($event) use ($projectionName, $expectedEvent, $exception) {
                return $event instanceof ProjectionError
                    && $event->projectionName === $projectionName
                    && $event->event === $expectedEvent
                    && $event->error === $exception;
            }));
    };
}

it('dispatch event when create projection', function (ProjectionStatus $status) {
    $this->repository->expects($this::once())->method('projectionName')->willReturn('projection-name');
    $this->repository->expects($this::once())->method('create')->with($status);

    $this->eventDispatcher->expects($this::once())->method('dispatch')->with($this->callback(function ($event) {
        return $event instanceof ProjectionCreated && $event->projectionName === 'projection-name';
    }));

    $this->eventDispatcherRepository->create($status);
})->with('projection status');

it('dispatch error event when create projection raise exception', function (Throwable $exception) {
    $status = ProjectionStatus::RUNNING;
    $this->repository->expects($this::once())->method('projectionName')->willReturn('projection-name');
    $this->repository->expects($this::once())->method('create')->with($status)->willThrowException($exception);

    assertErrorEventDispatched('projection-name', ProjectionCreated::class, $exception)($this);

    try {
        $this->eventDispatcherRepository->create($status);
    } catch (Throwable $e) {
        expect($e)->toBe($exception);
    }
})->with('exceptions');

it('dispatch event when start projection', function (ProjectionStatus $status) {
    $this->repository->expects($this::once())->method('projectionName')->willReturn('projection-name');
    $this->repository->expects($this::once())->method('start')->with($status);

    $this->eventDispatcher->expects($this::once())->method('dispatch')->with($this->callback(function ($event) {
        return $event instanceof ProjectionStarted && $event->projectionName === 'projection-name';
    }));

    $this->eventDispatcherRepository->start($status);
})->with('projection status');

it('dispatch error event when start projection raise exception', function (Throwable $exception) {
    $status = ProjectionStatus::RUNNING;
    $this->repository->expects($this::once())->method('projectionName')->willReturn('projection-name');
    $this->repository->expects($this::once())->method('start')->with($status)->willThrowException($exception);

    assertErrorEventDispatched('projection-name', ProjectionStarted::class, $exception)($this);

    try {
        $this->eventDispatcherRepository->start($status);
    } catch (Throwable $e) {
        expect($e)->toBe($exception);
    }
})->with('exceptions');

it('dispatch event when stop projection', function (ProjectionStatus $status) {
    $result = new ProjectionResult(['checkpoints'], ['user state']);

    $this->repository->expects($this::once())->method('projectionName')->willReturn('projection-name');
    $this->repository->expects($this::once())->method('stop')->with($result, $status);

    $this->eventDispatcher->expects($this::once())->method('dispatch')->with($this->callback(function ($event) {
        return $event instanceof ProjectionStopped && $event->projectionName === 'projection-name';
    }));

    $this->eventDispatcherRepository->stop($result, $status);
})->with('projection status');

it('dispatch error event when stop projection raise exception', function (Throwable $exception) {
    $status = ProjectionStatus::RUNNING;
    $result = new ProjectionResult(['checkpoints'], ['user state']);
    $this->repository->expects($this::once())->method('projectionName')->willReturn('projection-name');
    $this->repository->expects($this::once())->method('stop')->with($result, $status)->willThrowException($exception);

    assertErrorEventDispatched('projection-name', ProjectionStopped::class, $exception)($this);

    try {
        $this->eventDispatcherRepository->stop($result, $status);
    } catch (Throwable $e) {
        expect($e)->toBe($exception);
    }
})->with('exceptions');

it('dispatch event when start again projection', function (ProjectionStatus $status) {
    $this->repository->expects($this::once())->method('projectionName')->willReturn('projection-name');
    $this->repository->expects($this::once())->method('startAgain')->with($status);

    $this->eventDispatcher->expects($this::once())->method('dispatch')->with($this->callback(function ($event) {
        return $event instanceof ProjectionRestarted && $event->projectionName === 'projection-name';
    }));

    $this->eventDispatcherRepository->startAgain($status);
})->with('projection status');

it('dispatch event when reset projection', function (ProjectionStatus $status) {
    $result = new ProjectionResult(['checkpoints'], ['user state']);

    $this->repository->expects($this::once())->method('projectionName')->willReturn('projection-name');
    $this->repository->expects($this::once())->method('reset')->with($result, $status);

    $this->eventDispatcher->expects($this::once())->method('dispatch')->with($this->callback(function ($event) use ($result) {
        return $event instanceof ProjectionReset
            && $event->projectionName === 'projection-name'
            && $event->projectionResult === $result;
    }));

    $this->eventDispatcherRepository->reset($result, $status);
})->with('projection status');

it('dispatch error event when reset projection raise exception', function (Throwable $exception) {
    $status = ProjectionStatus::RUNNING;
    $result = new ProjectionResult(['checkpoints'], ['user state']);
    $this->repository->expects($this::once())->method('projectionName')->willReturn('projection-name');
    $this->repository->expects($this::once())->method('reset')->with($result, $status)->willThrowException($exception);

    assertErrorEventDispatched('projection-name', ProjectionReset::class, $exception)($this);

    try {
        $this->eventDispatcherRepository->reset($result, $status);
    } catch (Throwable $e) {
        expect($e)->toBe($exception);
    }
})->with('exceptions');

it('dispatch event when delete projection', function (bool $withEmittedEvents) {
    $this->repository->expects($this::once())->method('projectionName')->willReturn('projection-name');
    $this->repository->expects($this::once())->method('delete')->with($withEmittedEvents);

    $expectedEvent = $withEmittedEvents ? ProjectionDeletedWithEvents::class : ProjectionDeleted::class;

    $this->eventDispatcher->expects($this::once())->method('dispatch')->with($this->callback(function ($event) use ($expectedEvent) {
        return $event instanceof $expectedEvent && $event->projectionName === 'projection-name';
    }));

    $this->eventDispatcherRepository->delete($withEmittedEvents);
})->with('boolean');

it('dispatch error event when delete projection without emitted events raise exception', function (Throwable $exception) {
    $this->repository->expects($this::once())->method('projectionName')->willReturn('projection-name');
    $this->repository->expects($this::once())->method('delete')->with(false)->willThrowException($exception);

    assertErrorEventDispatched('projection-name', ProjectionDeleted::class, $exception)($this);

    try {
        $this->eventDispatcherRepository->delete(false);
    } catch (Throwable $e) {
        expect($e)->toBe($exception);
    }
})->with('exceptions');

it('dispatch error event when delete projection with emitted events raise exception', function (Throwable $exception) {
    $this->repository->expects($this::once())->method('projectionName')->willReturn('projection-name');
    $this->repository->expects($this::once())->method('delete')->with(true)->willThrowException($exception);

    assertErrorEventDispatched('projection-name', ProjectionDeletedWithEvents::class, $exception)($this);

    try {
        $this->eventDispatcherRepository->delete(true);
    } catch (Throwable $e) {
        expect($e)->toBe($exception);
    }
})->with('exceptions');

it('dispatch event when release projection', function () {
    $this->repository->expects($this::once())->method('projectionName')->willReturn('projection-name');
    $this->repository->expects($this::once())->method('release');

    $this->eventDispatcher->expects($this->once())->method('dispatch')->with($this->callback(function ($event) {
        return $event instanceof ProjectionReleased && $event->projectionName === 'projection-name';
    }));

    $this->eventDispatcherRepository->release();
});

test('persist projection result', function () {
    $projectionResult = new ProjectionResult(['checkpoints'], ['user state']);
    $this->repository->expects($this::once())->method('persist')->with($projectionResult);

    $this->eventDispatcherRepository->persist($projectionResult);
});

test('update lock', function () {
    $this->repository->expects($this::once())->method('updateLock');

    $this->eventDispatcherRepository->updateLock();
});

test('load status', function (ProjectionStatus $status) {
    $this->repository->expects($this::once())->method('loadStatus')->willReturn($status);

    expect($this->eventDispatcherRepository->loadStatus())->toBe($status);
})->with(['status' => ProjectionStatus::cases()]);

test('load detail', function () {
    $projectionResult = new ProjectionResult(['checkpoints'], ['user state']);
    $this->repository->expects($this::once())->method('loadDetail')->willReturn($projectionResult);

    expect($this->eventDispatcherRepository->loadDetail())->toBe($projectionResult);
});

test('exists', function (bool $exists) {
    $this->repository->expects($this::once())->method('exists')->willReturn($exists);

    expect($this->eventDispatcherRepository->exists())->toBe($exists);
})->with('boolean');
