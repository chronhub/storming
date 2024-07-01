<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Watcher;

use Storm\Projector\Workflow\Timer;
use Storm\Projector\Workflow\Watcher\TimeWatcher;

beforeEach(function () {
    $this->timer = mock(Timer::class);
    $this->watcher = new TimeWatcher($this->timer);
});

test('default instance', function () {
    $this->timer->shouldReceive('isStarted')->andReturn(false);

    expect($this->watcher->isStarted())->toBeFalse();
});

test('start timer', function () {
    $this->timer->shouldReceive('start')->once();

    $this->watcher->start();

    $this->timer->shouldReceive('isStarted')->andReturn(true);

    expect($this->watcher->isStarted())->toBeTrue();
});

test('reset timer', function () {
    $this->timer->shouldReceive('reset')->once();

    $this->watcher->reset();
});

test('get started time', function () {
    $this->timer->shouldReceive('getStartedTimestamp')->andReturn(1234567890);

    expect($this->watcher->getStartedTime())->toBe(1234567890);
});

test('get elapsed time', function () {
    $this->timer->shouldReceive('getElapsedTime')->andReturn(1234567890);

    expect($this->watcher->getElapsedTime())->toBe(1234567890);
});
