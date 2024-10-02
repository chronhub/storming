<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Subscription;

use Closure;
use Mockery\MockInterface;
use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\Repository;
use Storm\Projector\Projection\EmittingProjection;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Storage\ProjectionSnapshot;
use Storm\Projector\Stream\EmittedStream;
use Storm\Projector\Stream\EmittedStreamCache;
use Storm\Projector\Workflow\Notification\Command\CheckpointReset;
use Storm\Projector\Workflow\Notification\Command\EventStreamDiscovered;
use Storm\Projector\Workflow\Notification\Command\SprintStopped;
use Storm\Projector\Workflow\Notification\Command\UserStateRestored;
use Storm\Projector\Workflow\Notification\Promise\CurrentStatus;
use Storm\Stream\Stream;
use Storm\Stream\StreamName;
use Storm\Tests\Stubs\Double\Message\SomeEvent;

use function iterator_to_array;

beforeEach(function () {
    $this->hub = mock(NotificationHub::class);
    $this->repository = mock(Repository::class);
    $this->streamCache = mock(EmittedStreamCache::class);
    $this->emittedStream = mock(EmittedStream::class);
    $this->expectation = new ManagementExpectation($this->repository, $this->hub);
    $this->chronicler = mock(Chronicler::class);
    $this->management = new EmittingProjection(
        $this->hub,
        $this->chronicler,
        $this->repository,
        $this->streamCache,
        $this->emittedStream,
        1
    );
});

dataset('should create projection', [[false], [true]]);
dataset('checkpoints', [[[]], [[1, 5, 20]], [[1000, 5000]]]);
dataset('states', [[[]], [['foo']], [['bar']]]);

dataset('stream already emitted', [[true], [false]]);
dataset('stream already exists on first commit', [[true], [false]]);
dataset('stream already exists on append', [[true], [false]]);
dataset('stream in cache', [[true], [false]]);

test('rise projection', function (bool $alreadyCreated, ProjectionStatus $currentStatus) {
    $this->expectation->assertMountProjection($alreadyCreated, $currentStatus);

    $this->hub->expects('emit')->with(EventStreamDiscovered::class);

    $this->expectation->assertSynchronize();

    $this->management->rise();
})
    ->with('should create projection')
    ->with('projection status');

test('store projection snapshot', function (array $checkpoint, array $state) {
    $this->expectation->assertProjectionStore($checkpoint, $state);

    $this->management->store();
})
    ->with('checkpoints')
    ->with('states');

test('revise projection', function (array $checkpoint, array $state, ProjectionStatus $currentStatus) {
    $this->hub
        ->expects('emitMany')
        ->with(CheckpointReset::class, UserStateRestored::class);

    $this->expectation->assertProjectionSnapshot($checkpoint, $state);

    $this->hub->expects('await')->with(CurrentStatus::class)->andReturn($currentStatus);

    $this->repository->expects('reset')
        ->withArgs(
            fn (ProjectionSnapshot $result, ProjectionStatus $status) => $result->checkpoint === $checkpoint
                && $status === $currentStatus
        );

    // delete stream
    $this->expectation->assertProjectionName('foo');
    $this->chronicler->expects('delete')->withArgs(fn (StreamName $name) => $name->name === 'foo');

    // unlink stream
    $this->emittedStream->expects('unlink');

    $this->management->revise();
})
    ->with('checkpoints')
    ->with('states')
    ->with('projection status');

test('revise projection hold stream not found exception when delete a non existing stream', function (array $checkpoint, array $state, ProjectionStatus $currentStatus) {
    $this->hub
        ->expects('emitMany')
        ->with(CheckpointReset::class, UserStateRestored::class);

    $this->expectation->assertProjectionSnapshot($checkpoint, $state);

    $this->hub->expects('await')->with(CurrentStatus::class)->andReturn($currentStatus);

    $this->repository->expects('reset')
        ->withArgs(
            fn (ProjectionSnapshot $result, ProjectionStatus $status) => $result->checkpoint === $checkpoint
                && $status === $currentStatus
        );

    // delete stream
    $this->expectation->assertProjectionName('foo');
    $this->chronicler->expects('delete')
        ->withArgs(fn (StreamName $name) => $name->name === 'foo')
        ->andThrow(StreamNotFound::withStreamName(new StreamName('foo')));

    // unlink stream
    $this->emittedStream->expects('unlink');

    $this->management->revise();
})
    ->with('checkpoints')
    ->with('states')
    ->with('projection status');

test('discard projection', function (bool $withEmittedEvents) {
    $this->repository->expects('delete')->with($withEmittedEvents);

    // delete stream if withEmittedEvents
    if ($withEmittedEvents) {
        $this->expectation->assertProjectionName('foo');
        $this->chronicler->expects('delete')->withArgs(fn (StreamName $name) => $name->name === 'foo');
        $this->emittedStream->expects('unlink');
    } else {
        $this->chronicler->expects('delete')->never();
        $this->emittedStream->expects('unlink')->never();
    }

    $this->hub->expects('emit')->with(SprintStopped::class);
    $this->hub->expects('emitMany')->with(CheckpointReset::class, UserStateRestored::class);

    $this->management->discard($withEmittedEvents);
})->with('delete projection with emitted events');

function streamNotEmittedAndNotExists(string $streamName, bool $alreadyEmitted, bool $hasStream): Closure
{
    return function (Chronicler&MockInterface $chronicler, EmittedStream&MockInterface $emittedStream) use ($streamName, $alreadyEmitted, $hasStream) {
        $emittedStream->expects('wasEmitted')->andReturn($alreadyEmitted);

        ! $alreadyEmitted
          ? $chronicler->expects('hasStream')->withArgs(fn (StreamName $name) => $name->name === $streamName)->andReturn($hasStream)
          : $chronicler->expects('hasStream')->never();
    };
}

function streamIsCachedOrExists(string $streamName, bool $alreadyCached, bool $hasStream): Closure
{
    return function (Chronicler&MockInterface $chronicler, EmittedStreamCache&MockInterface $streamCache) use ($streamName, $alreadyCached, $hasStream) {
        $streamCache->expects('has')->with($streamName)->andReturn($alreadyCached);

        $alreadyCached
            ? $streamCache->expects('push')->never()
            : $streamCache->expects('push')->with($streamName);

        if (! $alreadyCached) {
            $chronicler->expects('hasStream')
                ->withArgs(fn (StreamName $name) => $name->name === $streamName)
                ->andReturn($hasStream);
        } else {
            $chronicler->expects('hasStream')->never();
        }
    };
}

test('emit event', function (bool $alreadyEmitted, bool $hasStream, bool $alreadyCached) {
    $projectionName = 'foo';
    $this->expectation->assertProjectionName($projectionName);

    $event = SomeEvent::fromContent(['name' => 'steph']);

    // stream was not emitted and streams does not exist
    streamNotEmittedAndNotExists($projectionName, $alreadyEmitted, $hasStream)($this->chronicler, $this->emittedStream);

    // append stream without the event only first commit
    if (! $alreadyEmitted && ! $hasStream) {
        $this->chronicler->expects('append')
            ->withArgs(function (Stream $stream) use ($projectionName) {
                $events = iterator_to_array($stream->events());

                return $events === [] && $stream->name->name === $projectionName;
            });

        $this->emittedStream->expects('emitted');
    } else {
        $this->chronicler->expects('append')->never();
        $this->emittedStream->expects('emitted')->never();
    }

    // hasStream true as we append stream on first commit, or it already exists in event store
    streamIsCachedOrExists($projectionName, $alreadyCached, true)($this->chronicler, $this->streamCache);

    // link to stream and append event
    $this->chronicler->expects('append')
        ->withArgs(function (Stream $stream) use ($projectionName, $event) {
            $events = iterator_to_array($stream->events());

            return $events[0] === $event && $stream->name->name === $projectionName;
        });

    $this->management->emit($event);
})->with('stream already emitted', 'stream already exists on first commit', 'stream in cache');

test('link to', function (bool $alreadyCached, bool $hasStream) {
    $projectionName = 'foo';
    $this->repository->shouldNotReceive('getName')->with($projectionName)->never();

    $newStreamName = 'stream1';
    streamIsCachedOrExists($newStreamName, $alreadyCached, $hasStream)($this->chronicler, $this->streamCache);

    $event = SomeEvent::fromContent(['name' => 'steph']);

    $this->chronicler->expects('append')
        ->withArgs(function (Stream $stream) use ($newStreamName, $event) {
            $events = iterator_to_array($stream->events());

            return $events[0] === $event && $stream->name->name === $newStreamName;
        });

    $this->management->linkTo($newStreamName, $event);
})->with('stream in cache', 'stream already exists on append');
