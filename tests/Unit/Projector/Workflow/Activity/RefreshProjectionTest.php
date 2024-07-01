<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Activity;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Activity\RefreshProjection;
use Storm\Projector\Workflow\Activity\RefreshRemoteStatus;
use Storm\Projector\Workflow\Notification\Stream\EventStreamDiscovered;

beforeEach(function () {
    $this->discovery = mock(RefreshRemoteStatus::class);
    $this->hub = mock(NotificationHub::class);
});

test('refresh projection and conditionally discover new streams', function (bool $onlyOnceDiscovery) {
    $activity = new RefreshProjection($this->discovery, $onlyOnceDiscovery);

    $this->discovery->expects('refresh')->with($this->hub);

    $onlyOnceDiscovery
        ? $this->hub->expects('notify')->never()
        : $this->hub->expects('notify')->with(EventStreamDiscovered::class);

    $return = $activity($this->hub, fn ($hub) => true);

    expect($return)->toBeTrue();
})->with([
    'only once discovery' => fn () => true,
    'discover on every cycle' => fn () => false,
]);
