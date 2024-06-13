<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow;

use Closure;
use RuntimeException;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Exception\ProjectionAlreadyRunning;
use Storm\Projector\Workflow\Notification\Management\ProjectionFreed;
use Storm\Projector\Workflow\Workflow;
use Throwable;

beforeEach(function () {
    $this->hub = $this->createMock(NotificationHub::class);
});

function getActivity(int &$count = 0): Closure
{
    return function (NotificationHub $hub, Closure $next) use (&$count) {
        $count++;
        $next($hub);
    };
}

function getActivitiesWithException(Throwable $exception): Closure
{
    return fn () => throw $exception;
}

function getDestination(bool $keepRunning): Closure
{
    return fn (NotificationHub $hub) => $keepRunning;
}

it('process and release projection', function () {
    $this->hub->expects($this->once())->method('trigger')->with(new ProjectionFreed());

    $called = 0;
    $instance = new Workflow($this->hub, [
        getActivity($called),
        getActivity($called),
    ]);

    $instance->process(getDestination(keepRunning: false));

    expect($called)->toBe(2);
});

it('process can be run again', function () {
    $this->hub->expects($this->exactly(2))->method('trigger')->with(new ProjectionFreed());

    $called = 0;
    $instance = new Workflow($this->hub, [
        getActivity($called),
        getActivity($called),
    ]);

    $instance->process(getDestination(keepRunning: false));
    $instance->process(getDestination(keepRunning: false));

    expect($called)->toBe(4);
});

it('process and raise original exception and ignore exception raise while releasing projection', function () {
    $exceptionIgnored = new RuntimeException('exception will be ignored');
    $this->hub->expects($this->once())->method('trigger')->with(new ProjectionFreed())->willThrowException($exceptionIgnored);

    $exception = new RuntimeException('foo');

    $called = 0;
    $instance = new Workflow($this->hub, [
        getActivity($called),
        getActivitiesWithException($exception),
    ]);

    try {
        $instance->process(getDestination(keepRunning: false));
    } catch (RuntimeException $e) {
        expect($e)->toBe($exception);
    }

    expect($called)->toBe(1);
});

it('raise exception and release projection', function () {
    $this->hub->expects($this->once())->method('trigger')->with(new ProjectionFreed());

    $exception = new RuntimeException('foo');

    $called = 0;
    $instance = new Workflow($this->hub, [
        getActivitiesWithException($exception),
        getActivity($called),
    ]);

    try {
        $instance->process(getDestination(keepRunning: true));
    } catch (RuntimeException $e) {
        expect($e)->toBe($exception);
    }

    expect($called)->toBe(0);
});

it('raise exception and does not release projection when exception is a projection already running instance', function () {
    $this->hub->expects($this->never())->method('trigger');

    $exception = new ProjectionAlreadyRunning('foo');

    $called = 0;
    $instance = new Workflow($this->hub, [
        getActivitiesWithException($exception),
        getActivity($called),
    ]);

    try {
        $instance->process(getDestination(keepRunning: false));
    } catch (RuntimeException $e) {
        expect($e)->toBe($exception);
    }

    expect($called)->toBe(0);
});

it('return false early and release projection', function () {
    $this->hub->expects($this->once())->method('trigger')->with(new ProjectionFreed());

    $called = 0;

    $instance = new Workflow($this->hub, [fn () => false]);
    $instance->process(getDestination(keepRunning: true));

    expect($called)->toBe(0);
});

it('keep running when destination return true', function () {
    $this->hub->expects($this->once())->method('trigger')->with(new ProjectionFreed());

    $called = 0;

    $instance = new Workflow($this->hub, [
        function (NotificationHub $hub, Closure $next) use (&$called): bool|Closure {
            $called++;

            if ($called === 5) {
                return false;
            }

            return $next($hub);
        },
    ]);

    $instance->process(getDestination(keepRunning: true));

    expect($called)->toBe(5);
});
