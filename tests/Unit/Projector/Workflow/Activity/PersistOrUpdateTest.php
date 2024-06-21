<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Activity;

use Closure;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Activity\PersistOrUpdate;
use Storm\Projector\Workflow\Notification\Batch\BatchSleep;
use Storm\Projector\Workflow\Notification\Batch\IsProcessBlank;
use Storm\Projector\Workflow\Notification\Checkpoint\HasGap;
use Storm\Projector\Workflow\Notification\Management\ProjectionLockUpdated;
use Storm\Projector\Workflow\Notification\Management\ProjectionStored;

beforeEach(function () {
    $this->hub = mock(NotificationHub::class);
    $this->activity = new PersistOrUpdate();
});

test('skip activity when gap detected', function () {
    $this->hub->shouldReceive('expect')->with(HasGap::class)->once()->andReturn(true);
    $this->hub->shouldNotReceive('notifyWhen')->never();

    ($this->activity)($this->hub, fn () => true);
});

test('sleep and update lock when no stream event to process', function () {
    $this->hub->shouldReceive('expect')->with(HasGap::class)->once()->andReturn(false);
    $this->hub->shouldReceive('expect')->with(IsProcessBlank::class)->once()->andReturn(true);
    $this->hub->shouldReceive('notifyWhen')->once()->withArgs(
        function (bool $condition, Closure $onSuccess) {
            $onSuccess($this->hub);

            return $condition === true;
        });

    $this->hub->shouldReceive('notify')->once()->with(BatchSleep::class);
    $this->hub->shouldReceive('trigger')->once()->withArgs(
        fn (object $trigger) => $trigger instanceof ProjectionLockUpdated
    );

    ($this->activity)($this->hub, fn () => true);
});

test('store stream event', function () {
    $this->hub->shouldReceive('expect')->with(HasGap::class)->once()->andReturn(false);
    $this->hub->shouldReceive('expect')->with(IsProcessBlank::class)->once()->andReturn(false);

    $this->hub->shouldReceive('notifyWhen')->once()->withArgs(
        function (bool $condition, Closure $onSuccess, Closure $onFailure) {
            $onFailure($this->hub);

            return $condition === false;
        });

    $this->hub->shouldReceive('notify')->with(BatchSleep::class)->never();
    $this->hub->shouldReceive('trigger')->once()->withArgs(
        fn (object $trigger) => $trigger instanceof ProjectionStored
    );

    ($this->activity)($this->hub, fn () => true);
});
