<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Watcher;

use Storm\Projector\Workflow\Watcher\CycleWatcher;

beforeEach(function () {
    $this->watcher = new CycleWatcher();
});

test('default instance', function () {
    expect($this->watcher->current())->toBe(0)
        ->and($this->watcher->isFirst())->toBeFalse()
        ->and($this->watcher->hasStarted())->toBeFalse();
});

test('start cycle', function () {
    $this->watcher->start();

    expect($this->watcher->current())->toBe(1)
        ->and($this->watcher->isFirst())->toBeTrue()
        ->and($this->watcher->hasStarted())->toBeTrue();
});

test('next cycle', function () {
    $this->watcher->start();
    $this->watcher->next();

    expect($this->watcher->current())->toBe(2)
        ->and($this->watcher->isFirst())->toBeFalse()
        ->and($this->watcher->hasStarted())->toBeTrue();
});

test('reset cycle', function () {
    $this->watcher->start();
    $this->watcher->next();
    $this->watcher->reset();

    expect($this->watcher->current())->toBe(0)
        ->and($this->watcher->isFirst())->toBeFalse()
        ->and($this->watcher->hasStarted())->toBeFalse();
});

test('increment cycles', function () {
    $this->watcher->start();
    $this->watcher->next();
    $this->watcher->next();
    $this->watcher->next();

    expect($this->watcher->current())->toBe(4)
        ->and($this->watcher->isFirst())->toBeFalse()
        ->and($this->watcher->hasStarted())->toBeTrue();
});

test('reset after many cycles', function () {
    $this->watcher->start();
    $this->watcher->next();
    $this->watcher->next();
    $this->watcher->next();
    $this->watcher->reset();

    expect($this->watcher->current())->toBe(0)
        ->and($this->watcher->isFirst())->toBeFalse()
        ->and($this->watcher->hasStarted())->toBeFalse();
});

test('start cycle after reset', function () {
    $this->watcher->start();
    $this->watcher->next();
    $this->watcher->next();
    $this->watcher->reset();
    $this->watcher->start();

    expect($this->watcher->current())->toBe(1)
        ->and($this->watcher->isFirst())->toBeTrue()
        ->and($this->watcher->hasStarted())->toBeTrue();
});

test('next cycle after reset', function () {
    $this->watcher->start();
    $this->watcher->next();
    $this->watcher->next();
    $this->watcher->reset();
    $this->watcher->next();

    expect($this->watcher->current())->toBe(1)
        ->and($this->watcher->isFirst())->toBeTrue()
        ->and($this->watcher->hasStarted())->toBeTrue();
});

test('reset cycle after start', function () {
    $this->watcher->start();
    $this->watcher->reset();

    expect($this->watcher->current())->toBe(0)
        ->and($this->watcher->isFirst())->toBeFalse()
        ->and($this->watcher->hasStarted())->toBeFalse();
});

test('reset cycle after next', function () {
    $this->watcher->start();
    $this->watcher->next();
    $this->watcher->reset();

    expect($this->watcher->current())->toBe(0)
        ->and($this->watcher->isFirst())->toBeFalse()
        ->and($this->watcher->hasStarted())->toBeFalse();
});
