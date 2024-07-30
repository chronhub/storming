<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow;

use Mockery\MockInterface;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\BeforeWorkflowRenewal;
use Storm\Projector\Workflow\Notification\Command\BatchStreamReset;
use Storm\Projector\Workflow\Notification\Command\MainCounterReset;
use Storm\Projector\Workflow\Notification\Command\NewEventStreamReset;
use Storm\Projector\Workflow\Notification\Command\StreamEventAckedReset;
use Storm\Projector\Workflow\Notification\Command\TimeReset;
use Storm\Projector\Workflow\Notification\Command\WorkflowCycleReset;
use Storm\Projector\Workflow\Notification\IsSprintTerminated;
use Storm\Projector\Workflow\Notification\Promise\StreamEventProcessed;
use Storm\Projector\Workflow\Notification\ShouldTerminateWorkflow;
use Storm\Projector\Workflow\Notification\SprintTerminated;
use Storm\Projector\Workflow\Notification\WorkflowBegan;
use Storm\Projector\Workflow\Notification\WorkflowCycleIncremented;
use Storm\Projector\Workflow\Notification\WorkflowRenewed;
use Storm\Projector\Workflow\Stage;

beforeEach(function () {
    $this->hub = mock(NotificationHub::class);
    $this->stage = new ExposedStage();
})->covers(Stage::class);

dataset('is sprint terminated', [true, false]);

function startStage(NotificationHub&MockInterface $hub, ExposedStage $stage): void
{
    expect($stage->hasStarted())->toBeFalse();

    $hub->expects('emit')->with(WorkflowBegan::class);

    $stage->beforeProcessing($hub);

    expect($stage->hasStarted())->toBeTrue();
}

test('default instance', function () {
    expect($this->stage->getResetsOnEveryCycle())->toBe([
        BatchStreamReset::class,
        StreamEventAckedReset::class,
        NewEventStreamReset::class,
    ])
        ->and($this->stage->getResetsOnTermination())->toBe([
            WorkflowCycleReset::class,
            TimeReset::class,
            MainCounterReset::class,
        ])
        ->and($this->stage->getForgetsOnTermination())->toBe([
            StreamEventProcessed::class,
        ])
        ->and($this->stage->getForgetsOnEveryCycle())->toBeEmpty()
        ->and($this->stage->hasStarted())->toBeFalse();
});

test('before processing', function () {
    startStage($this->hub, $this->stage);
});

test('does not start if already started', function () {
    startStage($this->hub, $this->stage);

    $this->stage->beforeProcessing($this->hub);
});

test('after processing', function (bool $isSprintTerminated) {
    startStage($this->hub, $this->stage);

    // should terminate
    $this->hub->expects()->emit(ShouldTerminateWorkflow::class);
    $this->hub->expects()->await(IsSprintTerminated::class)->andReturns($isSprintTerminated);

    $isSprintTerminated
        ? $this->hub->expects()->emit(SprintTerminated::class)
        : $this->hub->shouldNotReceive('emit')->with(SprintTerminated::class);

    // renew
    $this->hub->expects()->emit(BeforeWorkflowRenewal::class);
    $this->hub->expects()->emitMany(...$this->stage->getResetsOnEveryCycle());

    if ($isSprintTerminated) {
        $this->hub->expects()->emitMany(...$this->stage->getResetsOnTermination());
        $this->hub->expects()->forgetEvent(StreamEventProcessed::class);
    } else {
        $this->hub->expects()->emit(WorkflowCycleIncremented::class);
    }

    $this->hub->expects()->emit(WorkflowRenewed::class);

    expect($this->stage->afterProcessing($this->hub))->toBe($isSprintTerminated);

    $isSprintTerminated
        ? expect($this->stage->hasStarted())->toBeFalse()
        : expect($this->stage->hasStarted())->toBeTrue();
})->with('is sprint terminated');

test('stop', function () {
    startStage($this->hub, $this->stage);

    $this->stage->stop();
    expect($this->stage->hasStarted())->toBeFalse();
});
