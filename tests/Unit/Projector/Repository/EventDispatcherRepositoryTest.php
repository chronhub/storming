<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Repository;

use Closure;
use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use RuntimeException;
use Storm\Contract\Projector\ProjectionRepository;
use Storm\Projector\Checkpoint\CheckpointFactory;
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
    $this->repository = mock(ProjectionRepository::class);
    $this->eventDispatcher = mock(Dispatcher::class);
    $this->eventDispatcherRepository = new EventDispatcherRepository($this->repository, $this->eventDispatcher);
});

dataset('boolean', [true, false]);
dataset('exceptions', [new Exception('error message'), new RuntimeException('error message')]);

function assertErrorEventDispatched(string $projectionName, string $expectedEvent, Throwable $exception): Closure
{
    return function ($test) use ($projectionName, $expectedEvent, $exception) {
        $test->eventDispatcher->shouldReceive('dispatch')->withArgs(
            function (ProjectionError $event) use ($projectionName, $expectedEvent, $exception) {
                return $event->projectionName === $projectionName
                    && $event->event === $expectedEvent
                    && $event->error === $exception;
            })->once();
    };
}

function getProjectionResultStub(): ProjectionResult
{
    $checkpoint = CheckpointFactory::fromEmpty('stream_name', '2024-01-01 00:00:00');

    return new ProjectionResult(['foo' => $checkpoint], ['bar' => 'baz']);
}

it('dispatch event when create projection', function (ProjectionStatus $status) {
    $this->repository->shouldReceive('projectionName')->andReturn('projection-name')->once();
    $this->repository->shouldReceive('create')->with($status);

    $this->eventDispatcher->shouldReceive('dispatch')->withArgs(
        fn (ProjectionCreated $event) => $event->projectionName === 'projection-name'
    )->once();

    $this->eventDispatcherRepository->create($status);
})->with('projection status');

it('dispatch error event when create projection raise exception', function (Throwable $exception) {
    $status = ProjectionStatus::RUNNING;
    $this->repository->shouldReceive('projectionName')->andReturn('projection-name')->once();
    $this->repository->shouldReceive('create')->with($status)->andThrow($exception)->once();

    assertErrorEventDispatched('projection-name', ProjectionCreated::class, $exception)($this);

    try {
        $this->eventDispatcherRepository->create($status);
    } catch (Throwable $e) {
        expect($e)->toBe($exception);
    }
})->with('exceptions');

it('dispatch event when start projection', function (ProjectionStatus $status) {
    $this->repository->shouldReceive('projectionName')->andReturn('projection-name')->once();
    $this->repository->shouldReceive('start')->with($status)->once();

    $this->eventDispatcher->shouldReceive('dispatch')->withArgs(
        fn (ProjectionStarted $event) => $event->projectionName === 'projection-name'
    )->once();

    $this->eventDispatcherRepository->start($status);
})->with('projection status');

it('dispatch error event when start projection raise exception', function (Throwable $exception) {
    $status = ProjectionStatus::RUNNING;
    $this->repository->shouldReceive('projectionName')->andReturn('projection-name')->once();
    $this->repository->shouldReceive('start')->with($status)->andThrow($exception)->once();

    assertErrorEventDispatched('projection-name', ProjectionStarted::class, $exception)($this);

    try {
        $this->eventDispatcherRepository->start($status);
    } catch (Throwable $e) {
        expect($e)->toBe($exception);
    }
})->with('exceptions');

it('dispatch event when stop projection', function (ProjectionStatus $status) {
    $result = getProjectionResultStub();

    $this->repository->shouldReceive('projectionName')->andReturn('projection-name')->once();
    $this->repository->shouldReceive('stop')->with($result, $status)->once();

    $this->eventDispatcher
        ->shouldReceive('dispatch')
        ->withArgs(fn (ProjectionStopped $event) => $event->projectionName === 'projection-name')
        ->once();

    $this->eventDispatcherRepository->stop($result, $status);
})->with('projection status');

it('dispatch error event when stop projection raise exception', function (Throwable $exception) {
    $status = ProjectionStatus::RUNNING;
    $result = getProjectionResultStub();

    $this->repository->shouldReceive('projectionName')->andReturn('projection-name');
    $this->repository->shouldReceive('stop')->with($result, $status)->andThrow($exception);

    assertErrorEventDispatched('projection-name', ProjectionStopped::class, $exception)($this);

    try {
        $this->eventDispatcherRepository->stop($result, $status);
    } catch (Throwable $e) {
        expect($e)->toBe($exception);
    }
})->with('exceptions');

it('dispatch event when start again projection', function (ProjectionStatus $status) {
    $this->repository->shouldReceive('projectionName')->andReturn('projection-name')->once();
    $this->repository->shouldReceive('startAgain')->with($status)->once();

    $this->eventDispatcher
        ->shouldReceive('dispatch')
        ->withArgs(fn (ProjectionRestarted $event) => $event->projectionName === 'projection-name')
        ->once();

    $this->eventDispatcherRepository->startAgain($status);
})->with('projection status');

it('dispatch event when reset projection', function (ProjectionStatus $status) {
    $result = getProjectionResultStub();

    $this->repository->shouldReceive('projectionName')->andReturn('projection-name')->once();
    $this->repository->shouldReceive('reset')->with($result, $status)->once();

    $this->eventDispatcher->shouldReceive('dispatch')->withArgs(
        function (ProjectionReset $event) use ($result) {
            return $event->projectionName === 'projection-name'
                && $event->projectionResult === $result;
        });

    $this->eventDispatcherRepository->reset($result, $status);
})->with('projection status');

it('dispatch error event when reset projection raise exception', function (Throwable $exception) {
    $status = ProjectionStatus::RUNNING;
    $result = getProjectionResultStub();

    $this->repository->shouldReceive('projectionName')->andReturn('projection-name')->once();
    $this->repository->shouldReceive('reset')->with($result, $status)->andThrow($exception)->once();

    assertErrorEventDispatched('projection-name', ProjectionReset::class, $exception)($this);

    try {
        $this->eventDispatcherRepository->reset($result, $status);
    } catch (Throwable $e) {
        expect($e)->toBe($exception);
    }
})->with('exceptions');

it('dispatch event when delete projection', function (bool $withEmittedEvents) {
    $this->repository->shouldReceive('projectionName')->andReturn('projection-name')->once();
    $this->repository->shouldReceive('delete')->with($withEmittedEvents)->once();

    $expectedEvent = $withEmittedEvents ? ProjectionDeletedWithEvents::class : ProjectionDeleted::class;

    $this->eventDispatcher->shouldReceive('dispatch')
        ->withArgs(function (object $event) use ($expectedEvent) {
            return $event::class === $expectedEvent && $event->projectionName === 'projection-name';
        });

    $this->eventDispatcherRepository->delete($withEmittedEvents);
})->with('boolean');

it('dispatch error event when delete projection without emitted events raise exception', function (Throwable $exception) {
    $this->repository->shouldReceive('projectionName')->andReturn('projection-name')->once();
    $this->repository->shouldReceive('delete')->with(false)->andThrow($exception)->once();

    assertErrorEventDispatched('projection-name', ProjectionDeleted::class, $exception)($this);

    try {
        $this->eventDispatcherRepository->delete(false);
    } catch (Throwable $e) {
        expect($e)->toBe($exception);
    }
})->with('exceptions');

it('dispatch error event when delete projection with emitted events raise exception', function (Throwable $exception) {
    $this->repository->shouldReceive('projectionName')->andReturn('projection-name')->once();
    $this->repository->shouldReceive('delete')->with(true)->andThrow($exception)->once();

    assertErrorEventDispatched('projection-name', ProjectionDeletedWithEvents::class, $exception)($this);

    try {
        $this->eventDispatcherRepository->delete(true);
    } catch (Throwable $e) {
        expect($e)->toBe($exception);
    }
})->with('exceptions');

it('dispatch event when release projection', function () {
    $this->repository->shouldReceive('projectionName')->andReturn('projection-name')->once();
    $this->repository->shouldReceive('release')->once();

    $this->eventDispatcher->shouldReceive('dispatch')->withArgs(
        fn (ProjectionReleased $event) => $event->projectionName === 'projection-name'
    );

    $this->eventDispatcherRepository->release();
});

test('persist projection result', function () {
    $result = getProjectionResultStub();

    $this->repository->shouldReceive('persist')->with($result)->once();

    $this->eventDispatcherRepository->persist($result);
});

test('update lock', function () {
    $this->repository->shouldReceive('updateLock')->once();

    $this->eventDispatcherRepository->updateLock();
});

test('load status', function (ProjectionStatus $status) {
    $this->repository->shouldReceive('loadStatus')->andReturn($status)->once();

    expect($this->eventDispatcherRepository->loadStatus())->toBe($status);
})->with(['status' => ProjectionStatus::cases()]);

test('load detail', function () {
    $result = getProjectionResultStub();

    $this->repository->shouldReceive('loadDetail')->andReturn($result)->once();

    expect($this->eventDispatcherRepository->loadDetail())->toBe($result);
});

test('exists', function (bool $exists) {
    $this->repository->shouldReceive('exists')->andReturn($exists)->once();

    expect($this->eventDispatcherRepository->exists())->toBe($exists);
})->with('boolean');
