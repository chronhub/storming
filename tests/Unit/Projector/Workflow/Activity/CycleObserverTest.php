<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Activity;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Activity\CycleObserver;
use Storm\Projector\Workflow\Notification\Cycle\BeforeCycleRenewed;
use Storm\Projector\Workflow\Notification\Cycle\CycleBegan;
use Storm\Projector\Workflow\Notification\Cycle\CycleRenewed;
use Storm\Projector\Workflow\Notification\Sprint\IsSprintTerminated;

beforeEach(function () {
    $this->hub = mock(NotificationHub::class);
    $this->activity = new CycleObserver();
});

test('start and end cycle and return boolean depends on is sprint is terminated', function (bool $isSprintTerminated) {
    $this->hub->shouldReceive('notify')->with(CycleBegan::class)->once();
    $this->hub->shouldReceive('notify')->with(BeforeCycleRenewed::class)->once();
    $this->hub->shouldReceive('notify')->with(CycleRenewed::class)->once();
    $this->hub->shouldReceive('expect')->with(IsSprintTerminated::class)->once()->andReturn($isSprintTerminated);

    // the result of activity is never used
    $keepRunning = ($this->activity)($this->hub, fn ($hub) => null);

    expect($keepRunning)->toBe(! $isSprintTerminated);
})->with([
    'sprint is not terminated' => fn () => false,
    'sprint is terminated' => fn () => true,
]);
