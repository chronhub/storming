<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Watcher;

use Closure;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Support\AckedCounter;
use Storm\Projector\Support\CycleCounter;
use Storm\Projector\Support\MainCounter;
use Storm\Projector\Support\ProcessedCounter;
use Storm\Projector\Workflow\Agent\ReportAgent;
use Storm\Projector\Workflow\Notification\BeforeWorkflowRenewal;
use Storm\Projector\Workflow\Notification\Promise\CurrentElapsedTime;
use Storm\Projector\Workflow\Notification\Promise\CurrentStartedTime;
use Storm\Projector\Workflow\Notification\Promise\CurrentTime;
use Storm\Tests\Stubs\Double\Message\SomeEvent;

beforeEach(function () {
    $this->mainCounter = new MainCounter();
    $this->processedCounter = new ProcessedCounter(1000);
    $this->ackedCounter = new AckedCounter();
    $this->cycleCounter = new CycleCounter();

    $this->watcher = new ReportAgent(
        $this->mainCounter,
        $this->processedCounter,
        $this->ackedCounter,
        $this->cycleCounter
    );
    $this->hub = mock(NotificationHub::class);
});

dataset('should reset main event counter', [[true], [false]]);

test('default instance', function () {
    expect($this->watcher->getReport())->toBe([
        'started_at' => 0,
        'elapsed_time' => 0,
        'ended_at' => 0,
        'cycle' => 0,
        'acked_event' => 0,
        'total_event' => 0,
    ])->and($this->watcher->main())->toBe($this->mainCounter)
        ->and($this->watcher->processed())->toBe($this->processedCounter)
        ->and($this->watcher->acked())->toBe($this->ackedCounter)
        ->and($this->watcher->cycle())->toBe($this->cycleCounter);
});

function reportWatcherExpectation($that, int $times): void
{
    $that->hub->shouldReceive('await')->with(CurrentStartedTime::class)->andReturn(100000000)->times($times);
    $that->hub->shouldReceive('await')->with(CurrentElapsedTime::class)->andReturn(1)->times($times);
    $that->hub->shouldReceive('await')->with(CurrentTime::class)->andReturn(100000001)->times($times);
}

test('report', function (bool $doNotReset) {
    reportWatcherExpectation($this, 1);

    $this->mainCounter->doNotReset($doNotReset);

    $this->hub->expects('addEvent')
        ->withArgs(function (string $event, Closure $callback) {
            if ($event !== BeforeWorkflowRenewal::class) {
                return false;
            }

            $this->cycleCounter->next();
            $this->cycleCounter->next();
            $this->cycleCounter->next();

            $this->ackedCounter->merge(SomeEvent::class);
            $this->ackedCounter->merge(SomeEvent::class);
            $this->ackedCounter->merge(SomeEvent::class);

            $this->mainCounter->increment();
            $this->mainCounter->increment();
            $this->mainCounter->increment();
            $this->mainCounter->increment();

            $callback($this->hub);

            return true;
        });

    $this->watcher->subscribe($this->hub);

    expect($this->watcher->getReport())->toBe([
        'started_at' => 100000000,
        'elapsed_time' => 1,
        'ended_at' => 100000001,
        'cycle' => 3,
        'acked_event' => 3,
        'total_event' => 4,
    ]);
})->with('should reset main event counter');
