<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow;

use DateTimeImmutable;
use Storm\Contract\Clock\SystemClock;
use Storm\Projector\Exception\RuntimeException;
use Storm\Projector\Workflow\Timer;

beforeEach(function () {
    $this->clock = mock(SystemClock::class);
    $this->instance = new Timer($this->clock);
});

test('default instance', function () {
    expect($this->instance)->toBeInstanceOf(Timer::class)
        ->and($this->instance->isStarted())->toBeFalse();
});

test('test start', function () {
    $this->clock->expects('now')->andReturn(new DateTimeImmutable('2021-01-01 00:00:00'))->once();

    $this->instance->start();
    expect($this->instance->isStarted())->toBeTrue()
        ->and($this->instance->getStartedTimestamp())->toBe(1609459200);
});

test('reset timer', function () {
    $this->clock->shouldIgnoreMissing();

    $this->instance->start();
    expect($this->instance->isStarted())->toBeTrue();

    $this->instance->reset();
    expect($this->instance->isStarted())->toBeFalse();
});

test('reset timer does not throw exception when not started', function () {
    $this->instance->reset();

    expect($this->instance->isStarted())->toBeFalse();
});

test('get started timestamp', function () {
    $this->clock->expects('now')->andReturn(new DateTimeImmutable('2021-01-01 00:00:00'))->once();

    $this->instance->start();

    expect($this->instance->getStartedTimestamp())->toBe(1609459200);
});

test('get elapsed time', function () {
    $this->clock->expects('now')
        ->andReturn(
            new DateTimeImmutable('2021-01-01 00:00:00'),
            new DateTimeImmutable('2021-01-01 00:00:10')
        )->twice();

    $this->instance->start();
    expect($this->instance->getElapsedTime())->toBe(10);
});

describe('raise exception', function () {
    test('when get elapsed time not started', function () {
        $this->instance->getElapsedTime();
    })->throws(RuntimeException::class, 'Timer is not started');

    test('raise exception when get started time without started', function () {
        $this->instance->getStartedTimestamp();
    })->throws(RuntimeException::class, 'Timer is not started');

    test('when start again', function () {
        $this->clock->expects('now')->andReturn(new DateTimeImmutable('2021-01-01 00:00:00'))->once();

        $this->instance->start();
        expect($this->instance->isStarted())->toBeTrue()
            ->and($this->instance->getStartedTimestamp())->toBe(1609459200);

        $this->instance->start();
    })->throws(RuntimeException::class, 'Timer is already started');
});
