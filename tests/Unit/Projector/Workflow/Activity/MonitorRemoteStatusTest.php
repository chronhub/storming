<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Activity;

use Closure;
use Mockery;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Workflow\Activity\MonitorRemoteStatus;
use Storm\Projector\Workflow\Notification\Management\ProjectionClosed;
use Storm\Projector\Workflow\Notification\Management\ProjectionDiscarded;
use Storm\Projector\Workflow\Notification\Management\ProjectionRestarted;
use Storm\Projector\Workflow\Notification\Management\ProjectionRevised;
use Storm\Projector\Workflow\Notification\Management\ProjectionStatusDisclosed;
use Storm\Projector\Workflow\Notification\Management\ProjectionSynchronized;
use Storm\Projector\Workflow\Notification\Sprint\IsSprintDaemonize;
use Storm\Projector\Workflow\Notification\Status\CurrentStatus;

function getInstance(): object
{
    return new class
    {
        use MonitorRemoteStatus;

        public function isOnRise(): bool
        {
            return $this->onRise;
        }
    };
}

function discoverStatus(ProjectionStatus $status): Closure
{
    return function ($that) use ($status): void {
        $that->hub->shouldReceive('trigger')->once()->with(
            Mockery::on(fn ($trigger) => $trigger instanceof ProjectionStatusDisclosed)
        );

        $that->hub->shouldReceive('expect')->once()->with(
            Mockery::on(fn ($notification) => $notification === CurrentStatus::class)
        )->andReturn($status);
    };
}

function assertStopping(): Closure
{
    return function ($that): void {
        $firstExecution = $that->instance->isOnRise();

        if ($firstExecution) {
            $that->hub->shouldReceive('trigger')->once()->with(
                Mockery::on(fn ($trigger) => $trigger instanceof ProjectionSynchronized)
            );
        }

        $that->hub->shouldReceive('trigger')->once()->with(
            Mockery::on(fn ($trigger) => $trigger instanceof ProjectionClosed)
        );

        if ($firstExecution) {
            $shouldStop = $that->instance->shouldStop($that->hub);

            expect($shouldStop)->toBe($firstExecution);
        } else {
            $that->instance->refreshStatus($that->hub);
        }

        expect($that->instance->isOnRise())->toBeFalse();
    };
}

function assertResetting(bool $firstExecution, bool $runInBackground): Closure
{
    return function ($that) use ($firstExecution, $runInBackground): void {
        $that->hub->shouldReceive('trigger')->once()->with(
            Mockery::on(fn ($trigger) => $trigger instanceof ProjectionRevised)
        );

        if (! $firstExecution) {
            $that->hub->shouldReceive('expect')->once()->with(
                Mockery::on(fn ($notification) => $notification === IsSprintDaemonize::class)
            )->willReturn($runInBackground);

            if ($runInBackground) {
                $that->hub->shouldReceive('trigger')->once()->with(
                    Mockery::on(fn ($trigger) => $trigger instanceof ProjectionRestarted)
                );
            }
        }

        if ($firstExecution) {
            $shouldStop = $that->instance->shouldStop($that->hub);

            expect($shouldStop)->toBeFalse();
        } else {
            $that->instance->refreshStatus($that->hub);
        }

        expect($that->instance->isOnRise())->toBeFalse();
    };
}

function assertDeleting(bool $withEmittedEvents): Closure
{
    return function ($that) use ($withEmittedEvents): void {
        $firstExecution = $that->instance->isOnRise();

        $that->hub->shouldReceive('trigger')->once()->with(
            Mockery::on(fn ($trigger) => $trigger instanceof ProjectionDiscarded && $trigger->withEmittedEvents === $withEmittedEvents)
        );

        if ($firstExecution) {
            $shouldStop = $that->instance->shouldStop($that->hub);
            expect($shouldStop)->toBe($firstExecution);
        } else {
            $that->instance->refreshStatus($that->hub);
        }

        expect($that->instance->isOnRise())->toBeFalse();
    };
}

beforeEach(function () {
    $this->instance = getInstance();
    $this->hub = mock(NotificationHub::class);
});

test('default instance', function () {
    expect($this->instance->isOnRise())->toBeTrue();
});

test('should stop depends on disclosed status', function (ProjectionStatus $status) {
    expect($this->instance->isOnRise())->toBeTrue();

    discoverStatus($status)($this);

    switch ($status) {
        case ProjectionStatus::STOPPING:
            assertStopping()($this);

            break;

        case ProjectionStatus::DELETING:
            assertDeleting(false)($this);

            break;

        case ProjectionStatus::DELETING_WITH_EMITTED_EVENTS:
            assertDeleting(true)($this);

            break;

        default:
            $shouldStop = $this->instance->shouldStop($this->hub);

            expect($shouldStop)->toBeFalse()
                ->and($this->instance->isOnRise())->toBeFalse();
    }
})->with([
    ProjectionStatus::STOPPING,
    ProjectionStatus::DELETING,
    ProjectionStatus::DELETING_WITH_EMITTED_EVENTS,
    ProjectionStatus::RUNNING,
    ProjectionStatus::IDLE,
]);

test('never stop projection on discovering resetting status', function (bool $keepRunning) {
    expect($this->instance->isOnRise())->toBeTrue();

    discoverStatus(ProjectionStatus::RESETTING)($this);

    assertResetting(true, $keepRunning)($this);
})->with([true, false]);

test('refresh status at the end of each cycle', function (ProjectionStatus $status) {
    expect($this->instance->isOnRise())->toBeTrue();

    discoverStatus($status)($this);

    switch ($status) {
        case ProjectionStatus::STOPPING:
            assertStopping()($this);

            break;

        case ProjectionStatus::DELETING:
            assertDeleting(false)($this);

            break;

        case ProjectionStatus::DELETING_WITH_EMITTED_EVENTS:
            assertDeleting(true)($this);

            break;

        default:
            $this->instance->refreshStatus($this->hub);

            expect($this->instance->isOnRise())->toBeFalse();
    }
})->with([
    ProjectionStatus::STOPPING,
    ProjectionStatus::DELETING,
    ProjectionStatus::DELETING_WITH_EMITTED_EVENTS,
    ProjectionStatus::RUNNING,
    ProjectionStatus::IDLE,
]);
