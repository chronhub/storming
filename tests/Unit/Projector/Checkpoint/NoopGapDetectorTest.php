<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Checkpoint;

use LogicException;
use Storm\Contract\Projector\GapRecognition;
use Storm\Projector\Checkpoint\NoopGapDetector;

beforeEach(function () {
    $this->instance = new NoopGapDetector();
});

it('test default instance', function () {
    expect($this->instance)->toBeInstanceOf(GapRecognition::class)
        ->and($this->instance->hasGap())->toBeFalse()
        ->and($this->instance->isRecoverable())->toBeFalse()
        ->and($this->instance->hasRetry())->toBeFalse()
        ->and($this->instance->retryLeft())->toBe(0);
});

it('assert sleep does not perform any action', function () {
    $instance = $this->instance;

    $instance->sleep();
    expect($instance)->toBe($this->instance);
});

it('assert reset does not perform any action', function () {
    $instance = $this->instance;

    $instance->reset();
    expect($instance)->toBe($this->instance);
});

it('raise exception when gapType method is called', function () {
    $this->instance->gapType();
})->throws(LogicException::class, 'Method not available for class NoopGapDetector');
