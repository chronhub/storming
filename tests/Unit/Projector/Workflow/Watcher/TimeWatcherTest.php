<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Watcher;

use Storm\Projector\Workflow\Timer;
use Storm\Projector\Workflow\Watcher\TimeWatcher;

beforeEach(function () {
    $this->timer = $this->createStub(Timer::class);
    $this->watcher = new TimeWatcher($this->timer);
});

it('test new instance', function () {
    $this->timer->method('isStarted')->willReturn(false);

    expect($this->watcher->isStarted())->toBeFalse();
});

it('test start', function () {
    $this->timer->expects($this->once())->method('start');

    $this->watcher->start();

    $this->timer->method('isStarted')->willReturn(true);

    expect($this->watcher->isStarted())->toBeTrue();
});

it('test reset', function () {
    $this->timer->expects($this->once())->method('reset');

    $this->watcher->reset();
});

it('test get current time', function () {
    $this->timer->method('getTimestamp')->willReturn(1234567890);

    expect($this->watcher->getCurrentTime())->toBe(1234567890);
});

it('test get elapsed time', function () {
    $this->timer->method('getElapsedTime')->willReturn(1234567890);

    expect($this->watcher->getElapsedTime())->toBe(1234567890);
});
