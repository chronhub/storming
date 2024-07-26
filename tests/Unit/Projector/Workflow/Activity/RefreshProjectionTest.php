<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Activity;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Activity\RefreshPersistentProjection;
use Storm\Projector\Workflow\Activity\RefreshRemoteStatus;
use Storm\Projector\Workflow\Notification\Command\EventStreamDiscovered;

beforeEach(function () {
    $this->discovery = mock(RefreshRemoteStatus::class);
    $this->hub = mock(NotificationHub::class);
});

test('refresh projection and conditionally discover new streams', function (bool $onlyOnceDiscovery) {
    $activity = new RefreshPersistentProjection($this->discovery, $onlyOnceDiscovery);

    $this->discovery->expects('handle')->with($this->hub);

    $onlyOnceDiscovery
        ? $this->hub->expects('emit')->never()
        : $this->hub->expects('emit')->with(EventStreamDiscovered::class);

    $return = $activity($this->hub, fn ($hub) => true);

    expect($return)->toBeTrue();
})->with([
    'only once discovery' => fn () => true,
    'discover on every cycle' => fn () => false,
]);
