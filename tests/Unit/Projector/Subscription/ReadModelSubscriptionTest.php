<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Subscription;

use Closure;
use Factory\ActivityFactory;
use Scope\ReadModelScope;
use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\ReadModel;
use Storm\Projector\Projection\ReadModelProjection;
use Storm\Projector\Projection\ReadModelSubscription;
use Storm\Projector\Workflow\Component\Sprint;
use Storm\Projector\Workflow\ComponentRegistry;
use Storm\Projector\Workflow\Notification\BeforeWorkflowRenewal;
use Storm\Projector\Workflow\Notification\Command\UserStateRestored;
use Storm\Projector\Workflow\Notification\IsSprintTerminated;
use Storm\Projector\Workflow\Notification\ShouldTerminateWorkflow;
use Storm\Projector\Workflow\Notification\SprintTerminated;
use Storm\Projector\Workflow\Notification\WorkflowBegan;
use Storm\Projector\Workflow\Notification\WorkflowRenewed;

beforeEach(function () {
    $this->activities = mock(ActivityFactory::class);
    $this->projectorScope = mock(ReadModelScope::class);
    $this->hub = mock(NotificationHub::class);
    $this->management = mock(ReadModelProjection::class);
    $this->subscriptor = mock(ComponentRegistry::class);
    $this->readModel = mock(ReadModel::class);

    $this->subscription = new ReadModelSubscription(
        $this->subscriptor,
        $this->management,
        $this->activities,
    );
});

test('start projection', function (bool $keepRunning) {
    $this->hub->expects('emit')->with(UserStateRestored::class);
    $this->management->shouldReceive('hub')->andReturn($this->hub);

    // stage
    $this->hub->expects('emit')->with(WorkflowBegan::class);
    $this->hub->expects('emit')->with(ShouldTerminateWorkflow::class);
    $this->hub->expects('await')->with(IsSprintTerminated::class)->andReturn(true);
    $this->hub->expects('emit')->with(SprintTerminated::class);
    $this->hub->expects('emit')->with(BeforeWorkflowRenewal::class);
    $this->hub->expects('emit')->with(WorkflowRenewed::class);

    // stage reset on every cycle and sprint termination
    $this->hub->expects('emitMany')->twice();
    $this->hub->expects('forgetEvent');

    // initialize context
    $context = mock(ContextReader::class);
    $this->subscriptor->expects('setContext')->with($context);
    $this->subscriptor->expects('subscribe')->with($this->hub, $context);

    // setup watchers
    $sprintWatcher = mock(Sprint::class);
    $this->subscriptor->shouldReceive('sprint')->andReturn($sprintWatcher);

    $sprintWatcher->expects('runInBackground')->with($keepRunning);
    $sprintWatcher->expects('continue');

    // start projection
    $activities = [fn (NotificationHub $hub, Closure $next) => $next($hub)];
    $this->activities->expects('__invoke')->with($this->subscriptor)->andReturn($activities);

    $this->hub->expects('await')->with(IsSprintTerminated::class)->andReturn(true);

    $this->subscription->start($context, $keepRunning);
})->with([[true], [false]]);

test('interact with projection', function () {
    $this->management->shouldReceive('hub')->andReturn($this->hub);

    $this->hub->expects('await')->with(IsSprintTerminated::class)->andReturn(true);

    $result = $this->subscription->interact(fn (NotificationHub $hub) => $hub->await(IsSprintTerminated::class));

    expect($result)->toBeTrue();
});
