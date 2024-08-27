<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Support;

use Storm\Projector\Support\Metrics\MainMetric;

beforeEach(function () {
    $this->mainCounter = new MainMetric();
});

test('can be instantiated', function () {
    expect($this->mainCounter)->toBeInstanceOf(MainMetric::class)
        ->and($this->mainCounter->current())->toBe(0)
        ->and($this->mainCounter->isDoNotReset())->toBeFalse();
});

test('can increment', function () {
    $this->mainCounter->increment();
    expect($this->mainCounter->current())->toBe(1);

    $this->mainCounter->increment();
    expect($this->mainCounter->current())->toBe(2);
});

test('can reset', function () {
    $this->mainCounter->increment();
    $this->mainCounter->increment();

    expect($this->mainCounter->current())->toBe(2);

    $this->mainCounter->reset();
    expect($this->mainCounter->current())->toBe(0);
});

test('can set isDoNotReset', function () {
    $this->mainCounter->doNotReset(true);
    expect($this->mainCounter->isDoNotReset())->toBeTrue();

    $this->mainCounter->increment();
    expect($this->mainCounter->current())->toBe(1);

    $this->mainCounter->reset();
    expect($this->mainCounter->current())->toBe(1);
});
