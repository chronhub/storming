<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Repository;

use Storm\Clock\PointInTime;
use Storm\Contract\Clock\SystemClock;
use Storm\Projector\Exception\RuntimeException;
use Storm\Projector\Storage\LockManager;

beforeEach(function () {
    $this->clock = mock(SystemClock::class);
    $this->now = PointInTime::now();
});

test('default instance', function (int $lockTimeout, int $lockThreshold) {
    $lockManager = new LockManager($this->clock, $lockTimeout, $lockThreshold);

    expect($lockManager->lockTimeout)->toBe($lockTimeout)
        ->and($lockManager->lockThreshold)->toBe($lockThreshold);
})->with([
    [1000, 500],
    [2000, 1000],
    [3000, 1500],
    [3000, 0],
]);

test('raise exception when calling current null lock', function () {
    $lockManager = new LockManager($this->clock, 1000, 500);

    $lockManager->current();
})->throws(RuntimeException::class, 'Lock is not acquired');

test('acquire lock', function () {
    $now = PointInTime::fromString('2024-07-06T14:42:29.000000');

    $this->clock->expects('now')->andReturn($now);

    $lockManager = new LockManager($this->clock, 1000, 500);

    $currentLock = $lockManager->acquire();

    expect($currentLock)->toBe('2024-07-06T14:42:29.000000');
});

test('acquire lock again and override last lock', function () {
    $now = PointInTime::fromString('2024-07-06T14:42:29.000000');
    $later = PointInTime::fromString('2024-07-06T14:42:30.000000');

    $this->clock->expects('now')->andReturn($now);
    $this->clock->expects('now')->andReturn($later);

    $lockManager = new LockManager($this->clock, 1000, 500);

    $currentLock = $lockManager->acquire();
    expect($currentLock)->toBe('2024-07-06T14:42:29.000000');

    $currentLock = $lockManager->acquire();
    expect($currentLock)->toBe('2024-07-06T14:42:30.000000');
});

test('refresh lock by adding lock timeout to now', function () {
    $now = PointInTime::fromString('2024-07-06T14:42:29.000000');
    $this->clock->expects('now')->andReturn($now);

    $lockManager = new LockManager($this->clock, 1000, 500);

    $refreshedLock = $lockManager->refresh();

    expect($refreshedLock)->toBe('2024-07-06T14:42:30.000000')
        ->and($lockManager->current())->toBe('2024-07-06T14:42:30.000000');
});

test('should refresh lock when last lock is null', function () {
    $this->clock->expects('now')->never();

    $lockManager = new LockManager($this->clock, 1000, 500);

    expect($lockManager->shouldRefresh())->toBeTrue();
});

test('should refresh when lock threshold is 0', function () {
    $this->clock->expects('now')->never();
    $this->clock->expects('getFormat')->never();

    $lockManager = new LockManager($this->clock, 1000, 0);

    expect($lockManager->shouldRefresh())->toBeTrue();
});

test('should refresh when lock threshold is reached', function (int $lockThreshold) {
    $now = PointInTime::fromString('2024-07-06T14:42:29.000000');
    $later = PointInTime::fromString('2024-07-06T14:42:30.000000');

    $this->clock->expects('now')->andReturn($now);
    $this->clock->expects('now')->andReturn($later);

    $lockManager = new LockManager($this->clock, 1000, $lockThreshold);

    $lockManager->acquire();

    expect($lockManager->shouldRefresh())->toBeTrue();
})->with([
    [100],
    [150],
    [200],
    [500],
    [999],
]);

test('should not refresh when lock threshold is not reached', function (int $lockThreshold) {
    $now = PointInTime::fromString('2024-07-06T14:42:29.000000');
    $later = PointInTime::fromString('2024-07-06T14:42:30.000000');

    $this->clock->expects('now')->andReturn($now);
    $this->clock->expects('now')->andReturn($later);

    $lockManager = new LockManager($this->clock, 1000, $lockThreshold);

    $lockManager->acquire();

    expect($lockManager->shouldRefresh())->toBeFalse();
})->with([
    [1000],
    [1500],
    [2000],
    [2500],
    [3000],
]);
