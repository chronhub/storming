<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Watcher;

use Storm\Projector\Workflow\Watcher\CycleWatcher;

beforeEach(function () {
    $this->watcher = new CycleWatcher();
});

it('test new instance', function () {
    expect($this->watcher->current())->toBe(0)
        ->and($this->watcher->isFirst())->toBeFalse()
        ->and($this->watcher->hasStarted())->toBeFalse();
});

it('test start', function () {
    $this->watcher->start();

    expect($this->watcher->current())->toBe(1)
        ->and($this->watcher->isFirst())->toBeTrue()
        ->and($this->watcher->hasStarted())->toBeTrue();
});

it('test next', function () {
    $this->watcher->start();
    $this->watcher->next();

    expect($this->watcher->current())->toBe(2)
        ->and($this->watcher->isFirst())->toBeFalse()
        ->and($this->watcher->hasStarted())->toBeTrue();
});

it('test reset', function () {
    $this->watcher->start();
    $this->watcher->next();
    $this->watcher->reset();

    expect($this->watcher->current())->toBe(0)
        ->and($this->watcher->isFirst())->toBeFalse()
        ->and($this->watcher->hasStarted())->toBeFalse();
});

it('test multiple cycles', function () {
    $this->watcher->start();
    $this->watcher->next();
    $this->watcher->next();
    $this->watcher->next();

    expect($this->watcher->current())->toBe(4)
        ->and($this->watcher->isFirst())->toBeFalse()
        ->and($this->watcher->hasStarted())->toBeTrue();
});

it('test reset after multiple cycles', function () {
    $this->watcher->start();
    $this->watcher->next();
    $this->watcher->next();
    $this->watcher->next();
    $this->watcher->reset();

    expect($this->watcher->current())->toBe(0)
        ->and($this->watcher->isFirst())->toBeFalse()
        ->and($this->watcher->hasStarted())->toBeFalse();
});

it('test start after reset', function () {
    $this->watcher->start();
    $this->watcher->next();
    $this->watcher->next();
    $this->watcher->reset();
    $this->watcher->start();

    expect($this->watcher->current())->toBe(1)
        ->and($this->watcher->isFirst())->toBeTrue()
        ->and($this->watcher->hasStarted())->toBeTrue();
});

it('test next after reset', function () {
    $this->watcher->start();
    $this->watcher->next();
    $this->watcher->next();
    $this->watcher->reset();
    $this->watcher->next();

    expect($this->watcher->current())->toBe(1)
        ->and($this->watcher->isFirst())->toBeTrue()
        ->and($this->watcher->hasStarted())->toBeTrue();
});

it('test reset after start', function () {
    $this->watcher->start();
    $this->watcher->reset();

    expect($this->watcher->current())->toBe(0)
        ->and($this->watcher->isFirst())->toBeFalse()
        ->and($this->watcher->hasStarted())->toBeFalse();
});

it('test reset after next', function () {
    $this->watcher->start();
    $this->watcher->next();
    $this->watcher->reset();

    expect($this->watcher->current())->toBe(0)
        ->and($this->watcher->isFirst())->toBeFalse()
        ->and($this->watcher->hasStarted())->toBeFalse();
});
