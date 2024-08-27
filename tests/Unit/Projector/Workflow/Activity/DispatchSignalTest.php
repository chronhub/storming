<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Activity;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Activity\DispatchSignal;

use function pcntl_async_signals;
use function pcntl_signal;
use function posix_getpid;
use function posix_kill;

beforeEach(function () {
    $this->hub = mock(NotificationHub::class);

    $this->signalReceived = false;
    pcntl_signal(SIGUSR1, function () {
        $this->signalReceived = true;
    });
});

test('dispatch signal', function (bool $dispatchSignal) {
    $activity = new DispatchSignal($dispatchSignal);

    pcntl_async_signals(true);

    $next = function () use ($dispatchSignal) {
        if ($dispatchSignal) {
            posix_kill(posix_getpid(), SIGUSR1);
        }

        return true;
    };

    $activity($this->hub, $next);

    expect($this->signalReceived)->toBe($dispatchSignal);
})->with([[true], [false]]);
