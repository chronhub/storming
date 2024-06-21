<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Activity;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Activity\DiscoverRemoteStatus;
use Storm\Projector\Workflow\Activity\RisePersistentProjection;
use Storm\Projector\Workflow\Notification\Cycle\IsFirstCycle;
use Storm\Projector\Workflow\Notification\Management\ProjectionRise;

beforeEach(function () {
    $this->discovery = mock(DiscoverRemoteStatus::class);
    $this->hub = mock(NotificationHub::class);
    $this->activity = new RisePersistentProjection($this->discovery);
});

test('rise projection on first cycle', function () {
    $next = fn ($hub) => true;

    $this->hub->shouldReceive('expect')->with(IsFirstCycle::class)->once()->andReturn(true);
    $this->discovery->shouldReceive('onlyOnce')->with($this->hub)->once()->andReturn(false);
    $this->hub->shouldReceive('trigger')->withArgs(function (object $trigger) {
        return $trigger instanceof ProjectionRise;
    })->once();

    $this->assertTrue(($this->activity)($this->hub, $next));
});

test('return early when remote status is stopping or deleting', function () {
    $next = fn ($hub) => true;

    $this->hub->shouldReceive('expect')->with(IsFirstCycle::class)->once()->andReturn(true);
    $this->discovery->shouldReceive('onlyOnce')->with($this->hub)->once()->andReturn(true);
    $this->hub->shouldNotReceive('trigger');

    $this->assertFalse(($this->activity)($this->hub, $next));
});

test('skip activity when not on first cycle', function () {
    $next = fn ($hub) => true;

    $this->hub->shouldReceive('expect')->with(IsFirstCycle::class)->once()->andReturn(false);
    $this->discovery->shouldNotReceive('onlyOnce');
    $this->hub->shouldNotReceive('trigger');

    $this->assertTrue(($this->activity)($this->hub, $next));
});
