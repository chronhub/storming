<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Support;

use Storm\Projector\Support\Metrics\AckedMetric;
use Storm\Tests\Stubs\Double\Message\AnotherEvent;
use Storm\Tests\Stubs\Double\Message\SomeEvent;

beforeEach(function () {
    $this->counter = new AckedMetric();
});

test('it can be initialized', function () {
    expect($this->counter->getEvents())->toBeEmpty()
        ->and($this->counter->count())->toBe(0);
});

test('merge and increment', function () {
    $this->counter->increment(SomeEvent::class);
    expect($this->counter->count())->toBe(1);

    $this->counter->increment(AnotherEvent::class);

    expect($this->counter->count())->toBe(2)
        ->and($this->counter->getEvents())->toBe([SomeEvent::class, AnotherEvent::class]);
});

test('merge unique events', function () {
    $this->counter->increment(SomeEvent::class);
    $this->counter->increment(SomeEvent::class);
    expect($this->counter->count())->toBe(2);

    $this->counter->increment(AnotherEvent::class);

    expect($this->counter->count())->toBe(3)
        ->and($this->counter->getEvents())->toBe([SomeEvent::class, AnotherEvent::class]);
});

test('reset acked events', function () {
    $this->counter->increment(SomeEvent::class);
    $this->counter->increment(AnotherEvent::class);

    expect($this->counter->count())->toBe(2)
        ->and($this->counter->getEvents())->toBe([SomeEvent::class, AnotherEvent::class]);

    $this->counter->reset();

    expect($this->counter->count())->toBe(0)
        ->and($this->counter->getEvents())->toBeEmpty();
});
