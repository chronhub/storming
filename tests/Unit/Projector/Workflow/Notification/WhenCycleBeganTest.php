<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Notification;

use Closure;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\Command\TimeStarted;
use Storm\Projector\Workflow\Notification\Handler\WhenWorkflowBegan;
use Storm\Projector\Workflow\Notification\Promise\IsTimeStarted;
use Storm\Projector\Workflow\Notification\Promise\IsWorkflowStarted;
use Storm\Projector\Workflow\Notification\WorkflowBegan;
use Storm\Projector\Workflow\Notification\WorkflowStarted;

beforeEach(function () {
    $this->hub = mock(NotificationHub::class);
    $this->handler = new WhenWorkflowBegan();
    $this->event = new WorkflowBegan();
});

test('start cycle and time if not already started', function (bool $isCycleStarted, bool $isTimeStarted) {

    $this->hub->expects('emitWhen')
        ->withArgs(function (bool $isCycleStartedOrTimeNotStarted, Closure $callback) {
            if ($isCycleStartedOrTimeNotStarted) {
                $callback($this->hub);
            }

            return true;
        })
        ->twice()
        ->andReturn($this->hub);

    $this->hub->expects('await')->with(IsWorkflowStarted::class)->andReturn($isCycleStarted);
    $this->hub->expects('await')->with(IsTimeStarted::class)->andReturn($isTimeStarted);

    ! $isCycleStarted
        ? $this->hub->expects('emit')->with(WorkflowStarted::class)
        : $this->hub->shouldNotReceive('emit')->with(WorkflowStarted::class);

    ! $isTimeStarted
       ? $this->hub->expects('emit')->with(TimeStarted::class)
       : $this->hub->shouldNotReceive('emit')->with(TimeStarted::class);

    (new WhenWorkflowBegan())($this->hub, $this->event);
})
    ->with([[true], [false]])
    ->with([[true], [false]]);
