<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Support;

use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Support\Metrics\ProcessedMetric;

beforeEach(function () {
    $this->processedCounter = new ProcessedMetric(5);
});

test('can be instantiated', function () {
    expect($this->processedCounter)->toBeInstanceOf(ProcessedMetric::class)
        ->and($this->processedCounter->count())->toBe(0)
        ->and($this->processedCounter->threshold)->toBe(5)
        ->and($this->processedCounter->isLimitReached())->toBeFalse();
});

test('raises an exception if threshold is less than 1', function (int $threshold) {
    new ProcessedMetric($threshold);
})
    ->with([[-10], [-1], [0]])
    ->throws(InvalidArgumentException::class, 'Processed counter threshold must be greater than 0');

test('can be incremented', function () {
    $this->processedCounter->increment();
    expect($this->processedCounter->count())->toBe(1);

    $this->processedCounter->increment();
    expect($this->processedCounter->count())->toBe(2);
});

test('can be incremented beyond threshold', function () {
    expect($this->processedCounter->count())->toBe(0)
        ->and($this->processedCounter->threshold)->toBe(5);

    while ($this->processedCounter->count() < $this->processedCounter->threshold) {
        $this->processedCounter->increment();
    }

    expect($this->processedCounter->count())->toBe($this->processedCounter->threshold);

    $this->processedCounter->increment();
    expect($this->processedCounter->count())->toBe($this->processedCounter->threshold + 1);
});

test('can be reset', function () {
    $this->processedCounter->increment();
    expect($this->processedCounter->count())->toBe(1);

    $this->processedCounter->reset();
    expect($this->processedCounter->count())->toBe(0);
});

test('check threshold is not exceeded', function () {
    expect($this->processedCounter->isLimitReached())->toBeFalse();

    $count = $this->processedCounter->threshold;
    while ($count > 1) {
        $this->processedCounter->increment();
        $count--;
        expect($this->processedCounter->isLimitReached())->toBeFalse();
    }
});

test('check threshold is exceeded', function () {
    expect($this->processedCounter->isLimitReached())->toBeFalse();

    $count = $this->processedCounter->threshold;
    while ($count > 1) {
        $this->processedCounter->increment();
        $count--;
        expect($this->processedCounter->isLimitReached())->toBeFalse();
    }

    $this->processedCounter->increment();
    $this->processedCounter->increment();
    expect($this->processedCounter->isLimitReached())->toBeTrue()
        ->and($this->processedCounter->count())->toBe(6);
});
