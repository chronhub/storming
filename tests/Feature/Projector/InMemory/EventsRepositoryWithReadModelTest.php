<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\InMemory;

use Exception;
use Illuminate\Support\Facades\Event;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Scope\ReadModelScope;
use Storm\Projector\Store\Data\CreateData;
use Storm\Projector\Store\Events\ProjectionCreated;
use Storm\Projector\Store\Events\ProjectionDeleted;
use Storm\Projector\Store\Events\ProjectionDeletedWithEvents;
use Storm\Projector\Store\Events\ProjectionReleased;
use Storm\Projector\Store\Events\ProjectionReset;
use Storm\Projector\Store\Events\ProjectionRestarted;
use Storm\Projector\Store\Events\ProjectionStarted;
use Storm\Projector\Store\Events\ProjectionStopped;
use Storm\Projector\Store\ProjectionSnapshot;
use Storm\Projector\Support\ReadModel\InMemoryReadModel;
use Storm\Projector\Support\StopWhen;
use Storm\Tests\Feature\Projector\InMemory\Concern\InMemoryProjectionExpectationTrait;
use Storm\Tests\Feature\Projector\InMemory\Concern\InMemoryReadModelProjectionTestBaseTrait;
use Storm\Tests\Feature\Projector\InMemory\Factory\InMemoryTestingFactory;

uses(
    InMemoryReadModelProjectionTestBaseTrait::class,
    InMemoryProjectionExpectationTrait::class
);

beforeEach(function () {
    $this->factory = new InMemoryTestingFactory;
    $this->readModel = new InMemoryReadModel;

    $this->app['config']->set('projector.connection.in_memory.dispatch_events', true);
});

function assertDefaultDispatchedEvents(string $projectionName): void
{
    Event::assertDispatched(
        ProjectionCreated::class,
        function (ProjectionCreated $event) use ($projectionName) {
            return $event->name === $projectionName
                && $event->status === ProjectionStatus::RUNNING;
        });

    Event::assertDispatched(
        ProjectionStarted::class,
        function (ProjectionStarted $event) use ($projectionName) {
            return $event->name === $projectionName
                && $event->status === ProjectionStatus::RUNNING;
        });

    Event::assertDispatched(
        ProjectionReleased::class,
        function (ProjectionReleased $event) use ($projectionName) {
            return $event->projectionName === $projectionName;
        });
}

test('dispatch created, started, released events on first run', function () {
    Event::fake();

    $this->setupProjection(
        [[$streamName = 'account', null]],
        projectionName: $projectionName = 'balance',
    );

    $this->balanceEventStore($streamName)->withBalanceCreated(version: 1, amount: 100);

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($streamName)
        ->when($this->getReadModelReactor(), $this->getThenReactor())
        ->filter($this->factory->getQueryFilter())
        ->run(inBackground: false);

    assertDefaultDispatchedEvents($projectionName);
});

test('dispatch reset event', function () {
    Event::fake();

    $this->setupProjection(
        [[$streamName = 'account', null]],
        projectionName: $projectionName = 'balance',
    );

    $this->balanceEventStore($streamName)->withBalanceCreated(version: 1, amount: 100);

    $monitor = $this->factory->getMonitor();

    $thenReactor = function () use ($monitor, $projectionName): void {
        $monitor->markAsReset($projectionName);
    };

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($streamName)
        ->when($this->getReadModelReactor(), $thenReactor)
        ->filter($this->factory->getQueryFilter())
        ->run(inBackground: false);

    assertDefaultDispatchedEvents($projectionName);

    Event::assertDispatched(ProjectionReset::class, function (ProjectionReset $event) use ($projectionName) {
        return $event->name === $projectionName
            && $event->status === ProjectionStatus::RESETTING
            && $event->snapshot instanceof ProjectionSnapshot;
    });
});

test('dispatch restarted event when reset projection and running in background', function () {
    Event::fake();

    $this->setupProjection(
        [[$streamName = 'account', null]],
        projectionName: $projectionName = 'balance',
    );

    $this->balanceEventStore($streamName)->withBalanceCreated(version: 1, amount: 100);

    $monitor = $this->factory->getMonitor();

    $thenReactor = function (ReadModelScope $scope) use ($monitor, $projectionName): void {
        $monitor->markAsReset($projectionName);
    };

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($streamName)
        ->when($this->getReadModelReactor(), $thenReactor)
        ->haltOn(StopWhen::cycleReached(2))
        ->filter($this->factory->getQueryFilter())
        ->run(inBackground: true);

    assertDefaultDispatchedEvents($projectionName);

    Event::assertDispatched(ProjectionReset::class);
    Event::assertDispatched(ProjectionRestarted::class, function (ProjectionRestarted $event) use ($projectionName) {
        return $event->name === $projectionName && $event->status === ProjectionStatus::RUNNING;
    });
});

test('dispatch stop event', function () {
    Event::fake();

    $this->setupProjection(
        [[$streamName = 'account', null]],
        projectionName: $projectionName = 'balance',
    );

    $this->balanceEventStore($streamName)->withBalanceCreated(version: 1, amount: 100);

    $monitor = $this->factory->getMonitor();
    $thenReactor = function () use ($monitor, $projectionName): void {
        $monitor->markAsStop($projectionName);
    };

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($streamName)
        ->when($this->getReadModelReactor(), $thenReactor)
        ->filter($this->factory->getQueryFilter())
        ->run(inBackground: true);

    assertDefaultDispatchedEvents($projectionName);

    Event::assertDispatched(
        ProjectionStopped::class,
        function (ProjectionStopped $event) use ($projectionName) {
            return $event->name === $projectionName
                && $event->status === ProjectionStatus::IDLE
                && $event->snapshot instanceof ProjectionSnapshot;
        });
});

test('dispatch stop event on rise when discover stopping remote status', function () {
    Event::fake();

    $this->setupProjection(
        [[$streamName = 'account', null]],
        projectionName: $projectionName = 'balance',
    );

    $this->balanceEventStore($streamName)->withBalanceCreated(version: 1, amount: 100);

    // create first the projection
    $this->factory->getProjectionProvider()->createProjection(
        $projectionName,
        new CreateData(ProjectionStatus::STOPPING->value)
    );

    $thenReactor = function (): void {
        throw new Exception('should never been called');
    };

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($streamName)
        ->when($this->getReadModelReactor(), $thenReactor)
        ->filter($this->factory->getQueryFilter())
        ->run(inBackground: true);

    Event::assertDispatched(
        ProjectionStopped::class,
        function (ProjectionStopped $event) use ($projectionName) {
            return $event->name === $projectionName
                && $event->status === ProjectionStatus::IDLE
                && $event->snapshot instanceof ProjectionSnapshot;
        });
});

test('dispatch delete without emitted events', function () {
    Event::fake();

    $this->setupProjection(
        [[$streamName = 'account', null]],
        projectionName: $projectionName = 'balance',
    );

    $this->balanceEventStore($streamName)->withBalanceCreated(version: 1, amount: 100);

    $monitor = $this->factory->getMonitor();
    $thenReactor = function () use ($monitor, $projectionName): void {
        $monitor->markAsDelete($projectionName, false);
    };

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($streamName)
        ->when($this->getReadModelReactor(), $thenReactor)
        ->filter($this->factory->getQueryFilter())
        ->run(inBackground: true);

    Event::assertDispatched(ProjectionCreated::class);
    Event::assertDispatched(ProjectionStarted::class);
    Event::assertNotDispatched(ProjectionReleased::class);
    Event::assertNotDispatched(ProjectionStopped::class);

    Event::assertDispatched(
        ProjectionDeleted::class,
        function (ProjectionDeleted $event) use ($projectionName) {
            return $event->name === $projectionName;
        });
});

test('dispatch delete with emitted events', function () {
    Event::fake();

    $this->setupProjection(
        [[$streamName = 'account', null]],
        projectionName: $projectionName = 'balance',
    );

    $this->balanceEventStore($streamName)->withBalanceCreated(version: 1, amount: 100);

    $monitor = $this->factory->getMonitor();
    $thenReactor = function () use ($monitor, $projectionName): void {
        $monitor->markAsDelete($projectionName, true);
    };

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($streamName)
        ->when($this->getReadModelReactor(), $thenReactor)
        ->filter($this->factory->getQueryFilter())
        ->run(inBackground: true);

    Event::assertDispatched(ProjectionCreated::class);
    Event::assertDispatched(ProjectionStarted::class);
    Event::assertNotDispatched(ProjectionReleased::class);
    Event::assertNotDispatched(ProjectionStopped::class);

    Event::assertDispatched(
        ProjectionDeletedWithEvents::class,
        function (ProjectionDeletedWithEvents $event) use ($projectionName) {
            return $event->name === $projectionName;
        });
});

test('run projection again', function () {})->todo();
