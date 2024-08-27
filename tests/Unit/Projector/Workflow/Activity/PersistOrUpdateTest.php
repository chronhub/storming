<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Activity;

use Closure;
use Provider\Event\ProjectionLockUpdated;
use Provider\Event\ProjectionStored;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Activity\PersistOrUpdate;
use Storm\Projector\Workflow\Notification\Command\BatchStreamSleep;
use Storm\Projector\Workflow\Notification\Promise\HasGap;
use Storm\Projector\Workflow\Notification\Promise\IsBatchStreamBlank;

beforeEach(function () {
    $this->hub = mock(NotificationHub::class);
    $this->activity = new PersistOrUpdate();
});

test('skip activity when gap detected', function () {
    $this->hub->expects('await')->with(HasGap::class)->andReturn(true);
    $this->hub->expects('emitWhen')->never();

    ($this->activity)($this->hub, fn () => true);
});

test('sleep and update lock when no stream event to process', function () {
    $this->hub->expects('await')->with(HasGap::class)->andReturn(false);
    $this->hub->expects('await')->with(IsBatchStreamBlank::class)->andReturn(true);
    $this->hub->expects('emitWhen')->withArgs(
        function (bool $condition, Closure $onSuccess) {
            $onSuccess($this->hub);

            return $condition === true;
        });

    $this->hub->expects('emit')->with(BatchStreamSleep::class);
    $this->hub->expects('trigger')->withArgs(fn (ProjectionLockUpdated $trigger) => true);

    ($this->activity)($this->hub, fn () => true);
});

test('store stream event', function () {
    $this->hub->expects('await')->with(HasGap::class)->andReturn(false);
    $this->hub->expects('await')->with(IsBatchStreamBlank::class)->andReturn(false);

    $this->hub->expects('emitWhen')->withArgs(
        function (bool $condition, Closure $onSuccess, Closure $onFailure) {
            $onFailure($this->hub);

            return $condition === false;
        });

    $this->hub->expects('emit')->with(BatchStreamSleep::class)->never();
    $this->hub->expects('trigger')->withArgs(fn (ProjectionStored $trigger) => true);

    ($this->activity)($this->hub, fn () => true);
});
