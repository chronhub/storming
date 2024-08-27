<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Activity;

use Mockery\MockInterface;
use Provider\Event\ProjectionClosed;
use Provider\Event\ProjectionDiscarded;
use Provider\Event\ProjectionRestarted;
use Provider\Event\ProjectionRevised;
use Provider\Event\ProjectionStatusDisclosed;
use Provider\Event\ProjectionSynchronized;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Workflow\Notification\Promise\CurrentStatus;
use Storm\Projector\Workflow\Notification\Promise\IsSprintDaemonize;

class MonitorRemoteStatusExpectation
{
    public function discoverRemoteStatus(NotificationHub&MockInterface $hub, ProjectionStatus $status): void
    {
        $hub->expects('trigger')->withArgs(fn (ProjectionStatusDisclosed $trigger) => true);

        $hub->expects('await')
            ->withArgs(fn (string $notification) => $notification === CurrentStatus::class)
            ->andReturn($status);
    }

    public function expectsStopping(NotificationHub&MockInterface $hub, object $monitor): void
    {
        if ($monitor->onRise) {
            $hub->expects('trigger')->withArgs(fn (ProjectionSynchronized $trigger) => true);
        }

        $hub->expects('trigger')->withArgs(fn (ProjectionClosed $trigger) => true);

        $result = $monitor->handle($hub);

        expect($result)->toBe($monitor->onRise ? true : null);
    }

    public function expectsResetting(NotificationHub&MockInterface $hub, object $monitor, bool $runInBackground): void
    {
        $hub->expects('trigger')->withArgs(fn (ProjectionRevised $trigger) => true);

        if (! $monitor->onRise) {
            $hub->expects('await')
                ->withArgs(fn (string $notification) => $notification === IsSprintDaemonize::class)
                ->andReturn($runInBackground);

            if ($runInBackground) {
                $hub->expects('trigger')->withArgs(fn (ProjectionRestarted $trigger) => true);
            }
        }

        $result = $monitor->handle($hub);

        expect($result)->toBe($monitor->onRise ? false : null);
    }

    public function expectsDeleting(NotificationHub&MockInterface $hub, object $monitor, bool $withEmittedEvents): void
    {
        $hub->expects('trigger')->withArgs(
            fn (ProjectionDiscarded $trigger) => $trigger->withEmittedEvents === $withEmittedEvents
        );

        $result = $monitor->handle($hub);

        expect($result)->toBe($monitor->onRise ? true : null);
    }
}
