<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow;

use DateTimeImmutable;
use Storm\Contract\Clock\SystemClock;
use Storm\Projector\Exception\RuntimeException;
use Storm\Projector\Workflow\Timer;

beforeEach(function () {
    $this->clock = $this->createMock(SystemClock::class);
    $this->instance = new Timer($this->clock);
});

it('test instance', function () {
    expect($this->instance)->toBeInstanceOf(Timer::class)
        ->and($this->instance->isStarted())->toBeFalse();
});

it('test start', function () {
    $this->clock->expects($this->once())->method('now')->willReturn(new DateTimeImmutable('2021-01-01 00:00:00'));

    $this->instance->start();
    expect($this->instance->isStarted())->toBeTrue()
        ->and($this->instance->getTimestamp())->toBe(1609459200);
});

it('test reset', function () {
    $this->instance->start();
    expect($this->instance->isStarted())->toBeTrue();

    $this->instance->reset();
    expect($this->instance->isStarted())->toBeFalse();
});

it('test get timestamp', function () {
    $this->clock->expects($this->once())->method('now')->willReturn(new DateTimeImmutable('2021-01-01 00:00:00'));

    $this->instance->start();
    expect($this->instance->getTimestamp())->toBe(1609459200);
});

it('test get timestamp without start', function () {
    expect(fn () => $this->instance->getTimestamp())->toThrow(new RuntimeException('Timer is not started'));
});

it('test get elapsed time', function () {
    $this->clock->expects($this->exactly(2))
        ->method('now')
        ->willReturnOnConsecutiveCalls(
            new DateTimeImmutable('2021-01-01 00:00:00'),
            new DateTimeImmutable('2021-01-01 00:00:10')
        );

    $this->instance->start();
    expect($this->instance->getElapsedTime())->toBe(10);
});

it('test get elapsed time without start', function () {
    expect(fn () => $this->instance->getElapsedTime())->toThrow(new RuntimeException('Timer is not started'));
});

it('test get elapsed time with reset', function () {
    expect(fn () => $this->instance->getElapsedTime())->toThrow(new RuntimeException('Timer is not started'));
});
