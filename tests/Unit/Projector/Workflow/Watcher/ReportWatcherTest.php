<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Watcher;

use Closure;
use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\Cycle\BeforeCycleRenewed;
use Storm\Projector\Workflow\Notification\Cycle\CurrentCycle;
use Storm\Projector\Workflow\Notification\MasterCounter\CurrentMasterCount;
use Storm\Projector\Workflow\Notification\MasterCounter\ShouldResetMasterEventCounter;
use Storm\Projector\Workflow\Notification\Sprint\SprintTerminated;
use Storm\Projector\Workflow\Notification\Stream\CountEventAcked;
use Storm\Projector\Workflow\Notification\Timer\GetCurrentTime;
use Storm\Projector\Workflow\Notification\Timer\GetElapsedTime;
use Storm\Projector\Workflow\Notification\Timer\GetStartedTime;
use Storm\Projector\Workflow\Watcher\ReportWatcher;

beforeEach(function () {
    $this->watcher = new ReportWatcher();
    $this->hub = mock(NotificationHub::class);
});

dataset('should reset master event counter', [[true], [false]]);

test('default instance', function () {
    expect($this->watcher->getReport())->toBe([
        'started_at' => 0,
        'elapsed_time' => 0,
        'ended_at' => 0,
        'cycle' => 0,
        'acked_event' => 0,
        'total_event' => 0,
    ]);
});

function reportWatcherExpectation($that, int $times): void
{
    $that->hub->shouldReceive('expect')->with(GetStartedTime::class)->andReturn(100000000)->times($times);
    $that->hub->shouldReceive('expect')->with(GetElapsedTime::class)->andReturn(1)->times($times);
    $that->hub->shouldReceive('expect')->with(GetCurrentTime::class)->andReturn(100000001)->times($times);
    $that->hub->shouldReceive('expect')->with(CurrentCycle::class)->andReturn(1)->times($times);
    $that->hub->shouldReceive('expect')->with(CountEventAcked::class)->andReturn(10)->times($times);
    $that->hub->shouldReceive('expect')->with(CurrentMasterCount::class)->andReturn(100)->times($times);
}

test('report', function (bool $shouldResetMasterEventCounter) {
    reportWatcherExpectation($this, 1);

    // when calling once, it should return the same value
    $this->hub->shouldReceive('expect')->with(ShouldResetMasterEventCounter::class)
        ->andReturn($shouldResetMasterEventCounter);

    $this->hub->shouldReceive('addListener')->withArgs(
        function (string $event, Closure $callback) {
            if ($event !== SprintTerminated::class && $event !== BeforeCycleRenewed::class) {
                return false;
            }

            $callback($this->hub);

            return true;
        }
    )->twice();

    $this->watcher->subscribe($this->hub, mock(ContextReader::class));

    expect($this->watcher->getReport())->toBe([
        'started_at' => 100000000,
        'elapsed_time' => 1,
        'ended_at' => 100000001,
        'cycle' => 1,
        'acked_event' => 10,
        'total_event' => 100,
    ]);
})->with('should reset master event counter');

test('increment acked event', function (bool $shouldResetMasterEventCounter) {
    reportWatcherExpectation($this, 2);

    $this->hub->shouldReceive('expect')->with(ShouldResetMasterEventCounter::class)
        ->andReturn($shouldResetMasterEventCounter);

    $this->hub->shouldReceive('addListener')->withArgs(
        function (string $event, Closure $callback) {
            if ($event !== SprintTerminated::class && $event !== BeforeCycleRenewed::class) {
                return false;
            }

            // simulate multiple cycle
            $callback($this->hub);
            $callback($this->hub);

            return true;
        }
    )->twice();

    $this->watcher->subscribe($this->hub, mock(ContextReader::class));

    $report = $this->watcher->getReport();

    // dataset does not interfere with the result
    expect($report['acked_event'])->toBe(20);
})->with('should reset master event counter');

test('does not increment total event when master counter is not reset', function () {
    reportWatcherExpectation($this, 2);

    $this->hub->shouldReceive('expect')->with(ShouldResetMasterEventCounter::class)
        ->andReturn(false);

    $this->hub->shouldReceive('addListener')->withArgs(
        function (string $event, Closure $callback) {
            if ($event !== SprintTerminated::class && $event !== BeforeCycleRenewed::class) {
                return false;
            }

            // simulate multiple cycle
            $callback($this->hub);
            $callback($this->hub);

            return true;
        }
    )->twice();

    $this->watcher->subscribe($this->hub, mock(ContextReader::class));

    $report = $this->watcher->getReport();

    expect($report['total_event'])->toBe(100);
});

test('increment total event when master counter is reset', function () {
    reportWatcherExpectation($this, 2);

    $this->hub->shouldReceive('expect')->with(ShouldResetMasterEventCounter::class)
        ->andReturn(true);

    $this->hub->shouldReceive('addListener')->withArgs(
        function (string $event, Closure $callback) {
            if ($event !== SprintTerminated::class && $event !== BeforeCycleRenewed::class) {
                return false;
            }

            // simulate multiple cycle
            $callback($this->hub);
            $callback($this->hub);

            return true;
        }
    )->twice();

    $this->watcher->subscribe($this->hub, mock(ContextReader::class));

    $report = $this->watcher->getReport();

    expect($report['total_event'])->toBe(200);
});
