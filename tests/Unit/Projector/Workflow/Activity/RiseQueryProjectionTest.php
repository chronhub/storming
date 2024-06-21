<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Activity;

use Closure;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Activity\RiseQueryProjection;
use Storm\Projector\Workflow\Notification\Cycle\IsFirstCycle;
use Storm\Projector\Workflow\Notification\Stream\EventStreamDiscovered;

beforeEach(function () {
    $this->hub = mock(NotificationHub::class);
    $this->activity = new RiseQueryProjection();
});

test('discover new streams on first cycle', function () {

    $this->hub->expects('notifyWhen')
        ->once()
        ->withArgs(function (bool $notification, Closure $callback) {
            $callback($this->hub);

            return $notification === true;
        });

    $this->hub->expects('expect')
        ->once()
        ->with(IsFirstCycle::class)
        ->andReturn(true);

    $this->hub->expects('notify')
        ->once()
        ->with(EventStreamDiscovered::class);

    $return = ($this->activity)($this->hub, fn ($hub) => true);

    expect($return)->toBeTrue();
});

test('does not discover new streams when not on first cycle', function () {

    $this->hub->expects('notifyWhen')->once()->withArgs(
        fn (bool $notification) => $notification === false
    );

    $this->hub->expects('expect')->once()->with(IsFirstCycle::class)->andReturn(false);
    $this->hub->expects('notify')->never()->with(EventStreamDiscovered::class);

    $return = ($this->activity)($this->hub, fn ($hub) => true);

    expect($return)->toBeTrue();
});
