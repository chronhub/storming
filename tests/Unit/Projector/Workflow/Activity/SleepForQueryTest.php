<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Activity;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Activity\SleepForQuery;
use Storm\Projector\Workflow\Notification\Command\BatchStreamSleep;

beforeEach(function () {
    $this->activity = new SleepForQuery();
});

test('notify batch sleep', function () {
    $next = fn () => fn () => -1;
    $hub = mock(NotificationHub::class);
    $hub->expects('emit')->with(BatchStreamSleep::class);

    $process = ($this->activity)($hub, $next);

    expect($process())->toBe(-1);
});
