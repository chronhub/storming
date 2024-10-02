<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Watcher;

use Storm\Projector\Factory\Component\Timer;
use Storm\Projector\Support\Timer;

beforeEach(function () {
    $this->timer = mock(Timer::class);
    $this->watcher = new Timer($this->timer);
});

test('default instance', function () {
    $this->timer->expects('isStarted')->andReturn(false);

    expect($this->watcher->isStarted())->toBeFalse();
});

test('start timer', function () {
    $this->timer->expects('start')->once();

    $this->watcher->start();

    $this->timer->expects('isStarted')->andReturn(true);

    expect($this->watcher->isStarted())->toBeTrue();
});

test('reset timer', function () {
    $this->timer->expects('reset')->once();

    $this->watcher->reset();
});

test('get started time', function () {
    $this->timer->expects('getStartedTimestamp')->andReturn(1234567890);

    expect($this->watcher->getStartedTime())->toBe(1234567890);
});

test('get elapsed time', function () {
    $this->timer->expects('getElapsedTime')->andReturn(1234567890);

    expect($this->watcher->getElapsedTime())->toBe(1234567890);
});

test('get current timestamp', function () {
    $this->timer->expects('getCurrentTimestamp')->andReturn(1234567890);

    expect($this->watcher->getCurrentTime())->toBe(1234567890);
});
