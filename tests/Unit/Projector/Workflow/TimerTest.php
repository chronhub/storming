<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow;

use Carbon\CarbonImmutable;
use Storm\Clock\PointInTime;
use Storm\Contract\Clock\SystemClock;
use Storm\Projector\Exception\RuntimeException;
use Storm\Projector\Support\Timer;

beforeEach(function () {
    $this->pointInTime = PointInTime::fromString('2024-07-06T14:42:29.000000');
    $this->clock = mock(SystemClock::class);
    $this->instance = new Timer($this->clock);
});

test('default instance', function () {
    expect($this->instance)->toBeInstanceOf(Timer::class)
        ->and($this->instance->isStarted())->toBeFalse();
});

test('start timer', function () {
    $this->clock->expects('now')->andReturn($this->pointInTime);

    $this->instance->start();
    expect($this->instance->isStarted())->toBeTrue()
        ->and($this->instance->getStartedTimestamp())->toBe($this->pointInTime->carbon()->getTimestamp());
});

test('reset timer', function () {
    $this->clock->expects('now')->andReturn($this->pointInTime);
    $this->instance->start();
    expect($this->instance->isStarted())->toBeTrue();

    $this->instance->reset();
    expect($this->instance->isStarted())->toBeFalse();
});

test('reset timer does not throw exception when not started', function () {
    $this->instance->reset();

    expect($this->instance->isStarted())->toBeFalse();
});

test('get elapsed time', function () {
    $later = $this->pointInTime->modify(function (CarbonImmutable $carbon) {
        return $carbon->addSeconds(10);
    });

    $this->clock->expects('now')->andReturn($this->pointInTime);
    $this->clock->expects('now')->andReturn($later);

    $this->instance->start();
    expect($this->instance->getElapsedTime())->toBe(10);
});

test('get current timestamp', function () {
    $this->clock->expects('now')->andReturn($this->pointInTime)->twice();

    $this->instance->start();

    expect($this->instance->getCurrentTimestamp())->toBe($this->pointInTime->carbon()->getTimestamp());
});

describe('raise exception', function () {
    test('when get elapsed time not started', function () {
        $this->instance->getElapsedTime();
    })->throws(RuntimeException::class, 'Timer is not started');

    test('raise exception when get started time without started', function () {
        $this->instance->getStartedTimestamp();
    })->throws(RuntimeException::class, 'Timer is not started');

    test('when start again', function () {
        $this->clock->expects('now')->andReturn($this->pointInTime);

        $this->instance->start();

        expect($this->instance->isStarted())->toBeTrue()
            ->and($this->instance->getStartedTimestamp())->toBe($this->pointInTime->carbon()->getTimestamp());

        $this->instance->start();
    })->throws(RuntimeException::class, 'Timer is already started');
});
