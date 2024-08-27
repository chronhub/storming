<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Watcher;

use Closure;
use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Component\HaltOn;
use Storm\Projector\Workflow\Notification\Command\SprintStopped;
use Storm\Projector\Workflow\Notification\IsSprintTerminated;
use Storm\Projector\Workflow\Notification\ShouldTerminateWorkflow;
use Storm\Projector\Workflow\Notification\SprintTerminated;

beforeEach(function () {
    $this->hub = mock(NotificationHub::class);
    $this->context = mock(ContextReader::class);
    $this->watcher = new HaltOn();
});

test('stop when callback returns boolean', function (bool $shouldStop, bool $alreadyStopped): void {
    $callback = fn (NotificationHub $hub): bool => $shouldStop;
    $this->context->expects('haltOnCallback')->andReturn([$callback]);
    $this->hub->expects('forgetEvent')->with(ShouldTerminateWorkflow::class);

    $this->hub->expects('addEvent')->withArgs(function (string $event, Closure $forgetCallback): bool {
        if ($event === SprintTerminated::class) {
            $forgetCallback($this->hub);

            return true;
        }

        return false;
    });

    $this->hub->expects('await')->with(IsSprintTerminated::class)->andReturn($alreadyStopped);

    if ($alreadyStopped) {
        $this->hub->shouldNotReceive('emit')->with(SprintStopped::class);
    } else {
        $shouldStop
            ? $this->hub->expects('emit')->with(SprintStopped::class)
            : $this->hub->expects('emit')->with(SprintStopped::class)->never();
    }

    $this->hub->expects('addEvent')->withArgs(function (string $event, Closure $stopCallback): bool {
        if ($event !== ShouldTerminateWorkflow::class) {
            return false;
        }

        $stopCallback($this->hub);

        return true;
    });

    $this->watcher->subscribe($this->hub, $this->context);
})->with([[true], [false]], [[true], [false]]);

test('does not notify hub when callbacks is empty', function (): void {
    $this->context->expects('haltOnCallback')->andReturn([]);
    $this->hub->expects('forgetEvent')->never();
    $this->hub->expects('addEvent')->never();
    $this->hub->expects('emit')->never();

    $this->watcher->subscribe($this->hub, $this->context);
});
