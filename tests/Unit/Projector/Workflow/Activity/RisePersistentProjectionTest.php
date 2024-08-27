<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Activity;

use Provider\Event\ProjectionRise;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Activity\DiscoverRemoteStatus;
use Storm\Projector\Workflow\Activity\RisePersistentProjection;
use Storm\Projector\Workflow\Notification\Promise\IsFirstWorkflowCycle;

beforeEach(function () {
    $this->discovery = mock(DiscoverRemoteStatus::class);
    $this->hub = mock(NotificationHub::class);
    $this->activity = new RisePersistentProjection($this->discovery);
});

test('rise projection on first cycle', function () {
    $next = fn ($hub) => true;

    $this->hub->expects('await')->with(IsFirstWorkflowCycle::class)->andReturn(true);
    $this->discovery->expects('handle')->with($this->hub)->andReturn(false);
    $this->hub->expects('trigger')->withArgs(fn (ProjectionRise $trigger) => true);

    $this->assertTrue(($this->activity)($this->hub, $next));
});

test('return early when remote status is stopping or deleting', function () {
    $next = fn ($hub) => true;

    $this->hub->expects('await')->with(IsFirstWorkflowCycle::class)->andReturn(true);
    $this->discovery->expects('handle')->with($this->hub)->andReturn(true);
    $this->hub->expects('trigger')->never();

    $this->assertFalse(($this->activity)($this->hub, $next));
});

test('skip activity when not on first cycle', function () {
    $next = fn ($hub) => true;

    $this->hub->expects('await')->with(IsFirstWorkflowCycle::class)->andReturn(false);
    $this->discovery->expects('handle')->never();
    $this->hub->expects('trigger')->never();

    $this->assertTrue(($this->activity)($this->hub, $next));
});
