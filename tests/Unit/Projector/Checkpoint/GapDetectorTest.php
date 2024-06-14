<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Checkpoint;

use Storm\Contract\Projector\GapRecognition;
use Storm\Projector\Checkpoint\GapDetector;
use Storm\Projector\Exception\InvalidArgumentException;

it('test default instance', function () {
    $instance = new GapDetector([1, 2]);

    expect($instance)->toBeInstanceOf(GapRecognition::class)
        ->and($instance->hasGap())->toBeFalse()
        ->and($instance->isRecoverable())->toBeTrue()
        ->and($instance->hasRetry())->toBeTrue()
        ->and($instance->retryLeft())->toBe(2);
});

it('test instance with no retries', function () {
    new GapDetector([]);
})->throws('Provide at least one retry duration');

it('increment retries', function () {
    $instance = new GapDetector([1, 2]);

    expect($instance->retryLeft())->toBe(2)
        ->and($instance->hasGap())->toBeFalse()
        ->and($instance->isRecoverable())->toBeTrue()
        ->and($instance->hasGap())->toBeTrue();

    $instance->sleep();

    expect($instance->retryLeft())->toBe(1)
        ->and($instance->isRecoverable())->toBeTrue()
        ->and($instance->hasGap())->toBeTrue();

    $instance->sleep();

    expect($instance->retryLeft())->toBe(0)
        ->and($instance->isRecoverable())->toBeFalse()
        ->and($instance->hasGap())->toBeFalse();
});

it('reset retries and gap detected', function () {
    $instance = new GapDetector([1, 2]);

    expect($instance->retryLeft())->toBe(2)
        ->and($instance->hasGap())->toBeFalse()
        ->and($instance->isRecoverable())->toBeTrue()
        ->and($instance->hasGap())->toBeTrue();

    $instance->sleep();

    expect($instance->retryLeft())->toBe(1)
        ->and($instance->isRecoverable())->toBeTrue()
        ->and($instance->hasGap())->toBeTrue();

    $instance->reset();

    expect($instance->retryLeft())->toBe(2)
        ->and($instance->hasGap())->toBeFalse();
});

it('raise exception when call sleep if gap is not detected', function () {
    $instance = new GapDetector([1, 2]);

    expect($instance->retryLeft())->toBe(2)
        ->and($instance->hasGap())->toBeFalse();

    $instance->sleep();
})->throws(InvalidArgumentException::class, 'Gap not detected or no retries left');

it('raise exception when call sleep if retries are exhausted', function () {
    $instance = new GapDetector([1, 2]);

    expect($instance->retryLeft())->toBe(2)
        ->and($instance->hasGap())->toBeFalse()
        ->and($instance->isRecoverable())->toBeTrue()
        ->and($instance->hasGap())->toBeTrue();

    $instance->sleep();
    $instance->sleep();

    expect($instance->retryLeft())->toBe(0)
        ->and($instance->hasGap())->toBeTrue();

    $instance->sleep();
})->throws(InvalidArgumentException::class, 'Gap not detected or no retries left');
