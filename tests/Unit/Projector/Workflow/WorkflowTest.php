<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow;

use Closure;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\IsSprintTerminated;
use Storm\Projector\Workflow\Stage;
use Storm\Projector\Workflow\Workflow;
use Throwable;

dataset('is sprint terminated', [[true], [false]]);

beforeEach(function () {
    $this->hub = mock(NotificationHub::class);
    $this->stage = mock(Stage::class);
});

test('create workflow and run once', function () {
    $activities = [fn (NotificationHub $hub, Closure $next) => $next($hub)];

    $this->hub->expects('await')->with(IsSprintTerminated::class)->andReturn(true);
    $this->stage->expects('beforeProcessing')->with($this->hub);
    $this->stage->expects('afterProcessing')->with($this->hub);

    $workflow = Workflow::create($this->hub, $this->stage, $activities);

    $workflow->execute();
})->with([[false], [true]]);

test('create workflow and run once with early return', function (bool $booleanActivityReturn) {
    $activities = [fn (NotificationHub $hub, Closure $next) => $booleanActivityReturn];

    $this->hub->expects('await')->with(IsSprintTerminated::class)->andReturn(true);
    $this->stage->expects('beforeProcessing')->with($this->hub);
    $this->stage->expects('afterProcessing')->with($this->hub);

    $workflow = Workflow::create($this->hub, $this->stage, $activities);

    $workflow->execute();
})->with([[false], [true]]);

test('create workflow and keep running', function () {
    $activities = [
        fn (NotificationHub $hub, Closure $next) => $next($hub),
        fn (NotificationHub $hub, Closure $next) => false,
    ];

    $this->hub->expects('await')->with(IsSprintTerminated::class)->andReturn(false);
    $this->hub->expects('await')->with(IsSprintTerminated::class)->andReturn(true);
    $this->stage->expects('beforeProcessing')->with($this->hub)->twice();
    $this->stage->expects('afterProcessing')->with($this->hub)->twice();

    $workflow = Workflow::create($this->hub, $this->stage, $activities);

    $workflow->execute();
});

test('raise exception with no exception handler set', function () {
    $exception = new RuntimeException('foo');
    $activities = [
        fn (NotificationHub $hub, Closure $next) => throw $exception,
    ];

    $this->hub->shouldNotReceive('await')->with(IsSprintTerminated::class);
    $this->stage->expects('beforeProcessing')->with($this->hub);
    $this->stage->shouldNotReceive('afterProcessing')->with($this->hub);

    $workflow = Workflow::create($this->hub, $this->stage, $activities);

    $workflow->execute();
})->throws(RuntimeException::class, 'foo');

test('raise exception with exception handler set', function () {
    $exception = new RuntimeException('foo');
    $activities = [
        fn (NotificationHub $hub, Closure $next) => throw $exception,
    ];

    $this->hub->shouldNotReceive('await')->with(IsSprintTerminated::class);
    $this->stage->expects('beforeProcessing')->with($this->hub);
    $this->stage->shouldNotReceive('afterProcessing')->with($this->hub);

    $workflow = Workflow::create($this->hub, $this->stage, $activities);

    $workflow->withProcessReleaser(fn (Throwable $exception, NotificationHub $hub) => throw new Exception(
        $exception->getMessage(),
        $exception->getCode(),
        $exception
    ));

    $workflow->execute();
})->throws(Exception::class, 'foo');

test('prevent running the same instance again when an exception occurred', function () {
    $exception = new InvalidArgumentException('foo');
    $activities = [
        fn (NotificationHub $hub, Closure $next) => throw $exception,
    ];

    $this->hub->shouldNotReceive('await')->with(IsSprintTerminated::class);
    $this->stage->expects('beforeProcessing')->with($this->hub);
    $this->stage->shouldNotReceive('afterProcessing')->with($this->hub);

    $workflow = Workflow::create($this->hub, $this->stage, $activities);

    try {
        $workflow->execute();
    } catch (InvalidArgumentException) {
        // ignore
    }

    $workflow->execute();
})->throws(\Storm\Projector\Exception\RuntimeException::class, 'Running the projection again is not allowed after an exception has occurred.');
