<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Support;

use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Support\ExponentialSleep;

use function microtime;

beforeEach(function () {
    $this->expSleep = new ExponentialSleep();
});

test('initializes with default values', function () {
    expect($this->expSleep->base)->toBe(1000)
        ->and($this->expSleep->factor)->toBe(2.0)
        ->and($this->expSleep->max)->toBe(1000000);
});

test('initializes with custom values', function () {
    $customExpSleep = new ExponentialSleep(500, 1.5, 2000000);

    expect($customExpSleep->base)->toBe(500)
        ->and($customExpSleep->factor)->toBe(1.5)
        ->and($customExpSleep->max)->toBe(2000000);
});

test('raise exception with invalid values', function (int $base, float $factor, int $max) {
    new ExponentialSleep($base, $factor, $max);
})
    ->with([
        [-1, 2, 1000000],
        [0, -2, 1000000],
        [1, 0, 1000000],
        [1, -1, 1000000],
        [1, 2.5, 0],
        [1, 1, -1],
    ])->throws(InvalidArgumentException::class);

test('starts with base sleep time', function () {
    expect($this->expSleep->getSleepingTime())->toBe(1000);
});

test('increments sleep time', function () {
    $this->expSleep->increment();
    expect($this->expSleep->getSleepingTime())->toBe(2000);
});

test('caps sleep time at max sleep time', function () {
    $customExpSleep = new ExponentialSleep(1000, 2, 10000);
    for ($i = 0; $i < 10; $i++) {
        $customExpSleep->increment();
    }
    expect($customExpSleep->getSleepingTime())->toBe(10000);
});

test('resets to base sleep time', function () {
    $this->expSleep->increment();
    expect($this->expSleep->getSleepingTime())->toBe(2000);

    $this->expSleep->increment();
    expect($this->expSleep->getSleepingTime())->toBe(4000);

    $this->expSleep->reset();
    expect($this->expSleep->getSleepingTime())->toBe(1000);
});

test('sleeps for the correct duration', function () {
    $start = microtime(true);
    $this->expSleep->sleep();
    $end = microtime(true);
    $duration = ($end - $start) * 1000000;
    expect($duration)->toBeGreaterThanOrEqual(1000)->toBeLessThan(1100);
});

test('increments correctly with custom growth factor', function () {
    $customExpSleep = new ExponentialSleep(1000, 1.5);
    $customExpSleep->increment();
    expect($customExpSleep->getSleepingTime())->toBe(1500);
});

test('resets to base sleep time when max sleep time is reached', function () {
    $customExpSleep = new ExponentialSleep(1000, 2, 1000);
    for ($i = 0; $i < 10; $i++) {
        $customExpSleep->increment();
    }

    expect($customExpSleep->getSleepingTime())->toBe(1000);
});
