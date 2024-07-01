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

    $hub->shouldReceive('notifyWhen')
        ->withArgs(function (bool $hasGap, Closure $callback) use ($hub) {
            $callback($hub);

            return $hasGap === true;
        })->once();

    $hub->shouldReceive('expect')->once()->with(HasGap::class)->andReturn(true);
    $hub->shouldReceive('notify')->once()->with(SleepOnGap::class);
    $hub->shouldReceive('expect')->once()->with(IsBatchReset::class)->andReturn($resetBatch);

    if ($resetBatch) {
        $hub->shouldReceive('trigger')->never();
    } else {
        $hub->shouldReceive('trigger')->once()->withArgs(
            fn (object $trigger) => $trigger instanceof ProjectionStored
        );
    }

    $next = fn ($hub) => true;

    $handleStreamGap = new HandleStreamGap();
    $handleStreamGap($hub, $next);
})->with([
    'batch reset' => fn () => true,
    'batch not reset' => fn () => false,
]);

test('skip activity when no gap has been detected', function () {
    $hub = mock(NotificationHub::class);

    $hub->shouldReceive('notifyWhen')->once()->withArgs(
        fn (bool $hasGap) => $hasGap === false
    );

    $hub->shouldReceive('expect')->once()->with(HasGap::class)->andReturn(false);
    $hub->shouldReceive('notify')->never()->with(IsBatchReset::class);
    $hub->shouldReceive('notify')->never()->with(SleepOnGap::class);
    $hub->shouldReceive('trigger')->never();

    $next = fn ($hub) => true;

    $handleStreamGap = new HandleStreamGap();
    $handleStreamGap($hub, $next);
});
