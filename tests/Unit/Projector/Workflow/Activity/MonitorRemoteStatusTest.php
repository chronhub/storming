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

dataset('selected status', [
    'stopping' => fn () => ProjectionStatus::STOPPING,
    'deleting' => fn () => ProjectionStatus::DELETING,
    'deleting with emitted events' => fn () => ProjectionStatus::DELETING_WITH_EMITTED_EVENTS,
    'running' => fn () => ProjectionStatus::RUNNING,
    'idle' => fn () => ProjectionStatus::IDLE,
]);

function getInstance(bool $onRise): object
{
    return new class($onRise)
    {
        use MonitorRemoteStatus;

        public function __construct(public readonly bool $onRise)
        {
        }

        public function handle(NotificationHub $hub): ?bool
        {
            $result = $this->disclosedRemoteStatus($hub);

            return $this->onRise ? $result : null;
        }
    };
}

function discoverStatus(): Closure
{
    return function ($hub, ProjectionStatus $status): void {
        $hub->shouldReceive('trigger')->once()->with(
            Mockery::on(fn (object $trigger) => $trigger instanceof ProjectionStatusDisclosed)
        );

        $hub->shouldReceive('expect')->once()->with(
            Mockery::on(fn (string $notification) => $notification === CurrentStatus::class)
        )->andReturn($status);
    };
}

function assertStopping(): Closure
{
    return function ($hub, object $instance): void {
        if ($instance->onRise) {
            $hub->shouldReceive('trigger')->once()->with(
                Mockery::on(fn ($trigger) => $trigger instanceof ProjectionSynchronized)
            );
        }

        $hub->shouldReceive('trigger')->once()->with(
            Mockery::on(fn ($trigger) => $trigger instanceof ProjectionClosed)
        );

        $result = $instance->handle($hub);

        $instance->onRise
            ? expect($result)->toBe($instance->onRise)
            : expect($result)->toBeNull();
    };
}

function assertResetting(): Closure
{
    return function ($hub, $instance, $runInBackground): void {
        $hub->shouldReceive('trigger')->once()->with(
            Mockery::on(fn ($trigger) => $trigger instanceof ProjectionRevised)
        );

        if (! $instance->onRise) {
            $hub->shouldReceive('expect')->once()->with(
                Mockery::on(fn ($notification) => $notification === IsSprintDaemonize::class)
            )->andReturn($runInBackground);

            if ($runInBackground) {
                $hub->shouldReceive('trigger')->once()->with(
                    Mockery::on(fn ($trigger) => $trigger instanceof ProjectionRestarted)
                );
            }
        }

        $result = $instance->handle($hub);

        $instance->onRise
            ? expect($result)->toBeFalse()
            : expect($result)->toBeNull();
    };
}

function assertDeleting(): Closure
{
    return function ($hub, $instance, bool $withEmittedEvents): void {
        $hub->shouldReceive('trigger')->once()->with(
            Mockery::on(fn (object $trigger) => $trigger instanceof ProjectionDiscarded && $trigger->withEmittedEvents === $withEmittedEvents)
        );

        $result = $instance->handle($hub);

        $instance->onRise
            ? expect($result)->toBeTrue()
            : expect($result)->toBeNull();
    };
}

beforeEach(function () {
    $this->hub = mock(NotificationHub::class);
});

test('should stop depends on disclosed status', function (ProjectionStatus $status) {
    $instance = getInstance(true);

    discoverStatus()($this->hub, $status);

    switch ($status) {
        case ProjectionStatus::STOPPING:
            assertStopping()($this->hub, $instance);

            break;

        case ProjectionStatus::DELETING:
            assertDeleting()($this->hub, $instance, false);

            break;

        case ProjectionStatus::DELETING_WITH_EMITTED_EVENTS:
            assertDeleting()($this->hub, $instance, true);

            break;

        default:
            $result = $instance->handle($this->hub);

            expect($result)->toBeFalse();
    }
})->with('selected status');

test('never stop projection on discovering resetting status', function (bool $keepRunning) {
    $instance = getInstance(false);

    discoverStatus()($this->hub, ProjectionStatus::RESETTING);

    assertResetting()($this->hub, $instance, $keepRunning);
})->with([
    'keep running' => fn () => true,
    'run once' => fn () => false,
]);

test('refresh status at the end of each cycle', function (ProjectionStatus $status) {
    $instance = getInstance(false);
    expect($instance->onRise)->toBeFalse();

    discoverStatus()($this->hub, $status);

    switch ($status) {
        case ProjectionStatus::STOPPING:
            assertStopping()($this->hub, $instance);

            break;

        case ProjectionStatus::DELETING:
            assertDeleting()($this->hub, $instance, false);

            break;

        case ProjectionStatus::DELETING_WITH_EMITTED_EVENTS:
            assertDeleting()($this->hub, $instance, true);

            break;

        default:
            $result = $instance->handle($this->hub);

            expect($result)->toBeNull();
    }
})->with('selected status');
