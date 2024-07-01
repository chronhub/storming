<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Activity;

use Closure;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Activity\HandleStreamGap;
use Storm\Projector\Workflow\Notification\Batch\IsBatchReset;
use Storm\Projector\Workflow\Notification\Checkpoint\HasGap;
use Storm\Projector\Workflow\Notification\Checkpoint\SleepOnGap;
use Storm\Projector\Workflow\Notification\Management\ProjectionStored;

test('sleep on gap and store projection depends on batch is reset', function (bool $resetBatch) {
    $hub = mock(NotificationHub::class);

    $hub->expects('notifyWhen')
        ->withArgs(function (bool $hasGap, Closure $callback) use ($hub) {
            $callback($hub);

            return $hasGap === true;
        });

    $hub->expects('expect')->with(HasGap::class)->andReturn(true);
    $hub->expects('notify')->with(SleepOnGap::class);
    $hub->expects('expect')->with(IsBatchReset::class)->andReturn($resetBatch);

    $resetBatch
        ? $hub->shouldNotReceive('trigger')
        : $hub->expects('trigger')->withArgs(fn (ProjectionStored $trigger) => true);

    $next = fn ($hub) => true;

    $handleStreamGap = new HandleStreamGap();
    $handleStreamGap($hub, $next);
})->with([
    'reset batch' => fn () => true,
    'do not reset batch' => fn () => false,
]);

test('skip activity when no gap has been detected', function () {
    $hub = mock(NotificationHub::class);

    $hub->expects('notifyWhen')->withArgs(fn (bool $hasGap) => $hasGap === false);
    $hub->expects('expect')->with(HasGap::class)->andReturn(false);
    $hub->expects('notify')->never()->with(IsBatchReset::class);
    $hub->expects('notify')->never()->with(SleepOnGap::class);
    $hub->expects('trigger')->never();

    $next = fn ($hub) => true;

    $handleStreamGap = new HandleStreamGap();
    $handleStreamGap($hub, $next);
});
