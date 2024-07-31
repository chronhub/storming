<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Support;

use Storm\Projector\Support\Metrics\CycleMetric;

beforeEach(function () {
    $this->cycleCounter = new CycleMetric();
});

test('can be instantiated', function () {
    expect($this->cycleCounter)->toBeInstanceOf(CycleMetric::class)
        ->and($this->cycleCounter->current())->toBe(0)
        ->and($this->cycleCounter->hasStarted())->toBeFalse();
});

test('start cycle', function () {
    expect($this->cycleCounter->current())->toBe(0)
        ->and($this->cycleCounter->hasStarted())->toBeFalse();

    $this->cycleCounter->start();

    expect($this->cycleCounter->current())->toBe(1)
        ->and($this->cycleCounter->hasStarted())->toBeTrue();
});

test('can increment the cycle', function () {
    expect($this->cycleCounter->current())->toBe(0)
        ->and($this->cycleCounter->hasStarted())->toBeFalse();

    $this->cycleCounter->next();

    expect($this->cycleCounter->current())->toBe(1)
        ->and($this->cycleCounter->hasStarted())->toBeTrue();
});

test('can reset the cycle', function () {
    $this->cycleCounter->next();
    expect($this->cycleCounter->current())->toBe(1);

    $this->cycleCounter->reset();

    expect($this->cycleCounter->current())->toBe(0)
        ->and($this->cycleCounter->hasStarted())->toBeFalse();
});

test('can get the cycle', function () {
    $this->cycleCounter->next();
    expect($this->cycleCounter->current())->toBe(1);

    $this->cycleCounter->reset();
    expect($this->cycleCounter->current())->toBe(0);
});

test('check is first cycle', function () {
    expect($this->cycleCounter->isFirst())->toBeFalse();

    $this->cycleCounter->next();
    expect($this->cycleCounter->isFirst())->toBeTrue();

    $this->cycleCounter->reset();
    expect($this->cycleCounter->isFirst())->toBeFalse();
});

test('check has started', function () {
    expect($this->cycleCounter->hasStarted())->toBeFalse();

    $this->cycleCounter->next();
    expect($this->cycleCounter->hasStarted())->toBeTrue();

    $this->cycleCounter->reset();
    expect($this->cycleCounter->hasStarted())->toBeFalse();
});
