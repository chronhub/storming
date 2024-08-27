<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Activity;

use Closure;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Activity\RiseQueryProjection;
use Storm\Projector\Workflow\Notification\Command\EventStreamDiscovered;
use Storm\Projector\Workflow\Notification\Promise\IsFirstWorkflowCycle;

beforeEach(function () {
    $this->hub = mock(NotificationHub::class);
    $this->activity = new RiseQueryProjection();
});

test('discover new streams on first cycle', function () {
    $this->hub->expects('emitWhen')
        ->withArgs(function (bool $notification, Closure $callback) {
            $callback($this->hub);

            return $notification === true;
        });

    $this->hub->expects('await')->with(IsFirstWorkflowCycle::class)->andReturn(true);
    $this->hub->expects('emit')->with(EventStreamDiscovered::class);

    $return = ($this->activity)($this->hub, fn ($hub) => true);

    expect($return)->toBeTrue();
});

test('does not discover new streams when not on first cycle', function () {
    $this->hub->expects('emitWhen')->withArgs(fn (bool $notification) => $notification === false);
    $this->hub->expects('await')->with(IsFirstWorkflowCycle::class)->andReturn(false);
    $this->hub->expects('emit')->never()->with(EventStreamDiscovered::class);

    $return = ($this->activity)($this->hub, fn ($hub) => true);

    expect($return)->toBeTrue();
});
