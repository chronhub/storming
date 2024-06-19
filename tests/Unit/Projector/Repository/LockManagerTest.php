<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Repository;

use DateTimeImmutable;
use Storm\Contract\Clock\SystemClock;
use Storm\Projector\Exception\RuntimeException;
use Storm\Projector\Repository\LockManager;

beforeEach(function () {
    $this->clock = mock(SystemClock::class);
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
    $this->clock->expects('now')->andReturn(DateTimeImmutable::createFromFormat('U', '1630000000'));
    $this->clock->expects('getFormat')->andReturn('U');

    $lockManager = new LockManager($this->clock, 1000, 500);

    $currentLock = $lockManager->acquire();

    expect($currentLock)->toBe('1630000000');
});

test('acquire lock again and override last lock', function () {
    $this->clock
        ->expects('now')
        ->andReturn(DateTimeImmutable::createFromFormat('U', '1630000000'));

    $this->clock->expects('now')
        ->andReturn(DateTimeImmutable::createFromFormat('U', '1640000000'));

    $this->clock->expects('getFormat')->twice()->andReturn('U');

    $lockManager = new LockManager($this->clock, 1000, 500);

    $currentLock = $lockManager->acquire();
    expect($currentLock)->toBe('1630000000');

    $currentLock = $lockManager->acquire();
    expect($currentLock)->toBe('1640000000');
});

test('refresh lock by adding lock timeout to now', function () {
    $this->clock->expects('now')->andReturn(DateTimeImmutable::createFromFormat('U', '1630000000'));
    $this->clock->expects('getFormat')->twice()->andReturn('U');

    $lockManager = new LockManager($this->clock, 1000, 500);

    $refreshedLock = $lockManager->refresh();

    expect($refreshedLock)->toBe('1630000001')
        ->and($lockManager->current())->toBe('1630000001');
});

test('should refresh lock when last lock is null', function () {
    $this->clock->expects('now')->never();
    $this->clock->expects('getFormat')->never();

    $lockManager = new LockManager($this->clock, 1000, 500);

    expect($lockManager->shouldRefresh())->toBeTrue();
});

test('should refresh when lock threshold is 0', function () {
    $this->clock->expects('now')->never();
    $this->clock->expects('getFormat')->never();

    $lockManager = new LockManager($this->clock, 1000, 0);

    expect($lockManager->shouldRefresh())->toBeTrue();
});

test('should refresh when lock threshold is reached', function () {
    $this->clock->expects('now')->andReturn(DateTimeImmutable::createFromFormat('U', '1630000000'));
    $this->clock->expects('now')->andReturn(DateTimeImmutable::createFromFormat('U', '1630000001'));
    $this->clock->expects('getFormat')->once()->andReturn('U');

    $lockManager = new LockManager($this->clock, 1000, 500);

    $lockManager->acquire();

    expect($lockManager->shouldRefresh())->toBeTrue();
});
