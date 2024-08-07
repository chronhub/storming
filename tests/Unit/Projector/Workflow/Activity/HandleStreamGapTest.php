<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Activity;

use Closure;
use Provider\Event\ProjectionStored;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Activity\HandleStreamGap;
use Storm\Projector\Workflow\Notification\Command\SleepOnGap;
use Storm\Projector\Workflow\Notification\Promise\HasGap;
use Storm\Projector\Workflow\Notification\Promise\IsBatchStreamReset;

test('sleep on gap and store projection depends on batch is reset', function (bool $resetBatch) {
    $hub = mock(NotificationHub::class);

    $hub->expects('emitWhen')
        ->withArgs(function (bool $hasGap, Closure $callback) use ($hub) {
            $callback($hub);

            return $hasGap === true;
        });

    $hub->expects('await')->with(HasGap::class)->andReturn(true);
    $hub->expects('emit')->with(SleepOnGap::class);
    $hub->expects('await')->with(IsBatchStreamReset::class)->andReturn($resetBatch);

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

    $hub->expects('emitWhen')->withArgs(fn (bool $hasGap) => $hasGap === false);
    $hub->expects('await')->with(HasGap::class)->andReturn(false);
    $hub->expects('emit')->never()->with(IsBatchStreamReset::class);
    $hub->expects('emit')->never()->with(SleepOnGap::class);
    $hub->expects('trigger')->never();

    $next = fn ($hub) => true;

    $handleStreamGap = new HandleStreamGap();
    $handleStreamGap($hub, $next);
});
