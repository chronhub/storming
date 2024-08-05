<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Checkpoint;

use Checkpoint\GapRecognition;
use Storm\Projector\Checkpoint\GapDetector;
use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Exception\RuntimeException;

use function count;
use function range;

test('default instance', function () {
    $instance = new GapDetector([1, 2]);

    expect($instance)->toBeInstanceOf(GapRecognition::class)
        ->and($instance->hasGap())->toBeFalse()
        ->and($instance->recover())->toBeTrue()
        ->and($instance->hasRetry())->toBeTrue()
        ->and($instance->retryLeft())->toBe(2);
});

test('instance with no retries', function () {
    $instance = new GapDetector([]);

    expect($instance->hasRetry())->toBeFalse()
        ->and($instance->retryLeft())->toBe(0);
});

test('increment retries by sleeping', function (array $retries) {
    $countRetries = count($retries);
    $instance = new GapDetector($retries);

    expect($instance->retryLeft())->toBe(count($retries))
        ->and($instance->hasGap())->toBeFalse()
        ->and($instance->recover())->toBeTrue()
        ->and($instance->hasGap())->toBeTrue();

    for ($i = 0; $i < $countRetries; $i++) {
        $instance->sleep();

        expect($instance->retryLeft())->toBe($countRetries - $i - 1);

        if ($instance->retryLeft() > 0) {
            expect($instance->hasGap())->toBeTrue()
                ->and($instance->recover())->toBeTrue();
        } else {
            expect($instance->hasGap())->toBeTrue()
                ->and($instance->recover())->toBeFalse(); //no retries left and reset gap detection
        }
    }

    expect($instance->hasGap())->toBefalse()
        ->and($instance->recover())->toBeTrue();
})->with([
    'with one retry' => [[1]],
    'with two retries' => [[1, 2]],
    'with ten retries' => fn (): array => range(1, 10),
]);

test('reset retries and gap detected', function () {
    $instance = new GapDetector([1, 2]);

    expect($instance->retryLeft())->toBe(2)
        ->and($instance->hasGap())->toBeFalse()
        ->and($instance->recover())->toBeTrue()
        ->and($instance->hasGap())->toBeTrue();

    $instance->sleep();

    expect($instance->retryLeft())->toBe(1)
        ->and($instance->recover())->toBeTrue()
        ->and($instance->hasGap())->toBeTrue();

    $instance->reset();

    expect($instance->retryLeft())->toBe(2)
        ->and($instance->hasGap())->toBeFalse();
});

test('raise exception with call sleep when gap is not detected', function () {
    $instance = new GapDetector([1, 2]);

    expect($instance->retryLeft())->toBe(2)
        ->and($instance->hasGap())->toBeFalse();

    $instance->sleep();
})->throws(RuntimeException::class, 'Gap not detected or no retries left');

test('raise exception with call sleep when retries are exhausted', function () {
    $instance = new GapDetector([1, 2]);

    expect($instance->retryLeft())->toBe(2)
        ->and($instance->hasGap())->toBeFalse()
        ->and($instance->recover())->toBeTrue()
        ->and($instance->hasGap())->toBeTrue();

    $instance->sleep();
    $instance->sleep();

    expect($instance->retryLeft())->toBe(0)
        ->and($instance->hasGap())->toBeTrue();

    $instance->sleep();
})->throws(RuntimeException::class, 'Gap not detected or no retries left');

test('gap type depends on retries left', function () {
    $instance = new GapDetector([1, 2]);

    expect($instance->retryLeft())->toBe(2)
        ->and($instance->recover())->toBeTrue()
        ->and($instance->gapType())->toBe(GapType::RECOVERABLE_GAP);

    $instance->sleep();

    expect($instance->retryLeft())->toBe(1)
        ->and($instance->recover())->toBeTrue()
        ->and($instance->gapType())->toBe(GapType::UNRECOVERABLE_GAP);

    $instance->sleep();

    expect($instance->retryLeft())->toBe(0)
        ->and($instance->gapType())->toBe(GapType::IN_GAP);
});

test('assert gap type depends on retries left 2', function (array $retries) {
    $countRetries = count($retries);
    $instance = new GapDetector($retries);

    expect($instance->retryLeft())->toBe(count($retries))
        ->and($instance->hasGap())->toBeFalse()
        ->and($instance->recover())->toBeTrue()
        ->and($instance->hasGap())->toBeTrue();

    for ($i = 0; $i < $countRetries; $i++) {
        $instance->sleep();

        expect($instance->retryLeft())->toBe($countRetries - $i - 1);

        if ($instance->retryLeft() === 0) {
            expect($instance->gapType())->toBe(GapType::IN_GAP);
        } elseif ($instance->retryLeft() === 1) {
            expect($instance->gapType())->toBe(GapType::UNRECOVERABLE_GAP);
        } else {
            expect($instance->gapType())->toBe(GapType::RECOVERABLE_GAP);
        }
    }

    // we just exhausted all retries, but not called is recoverable method
    // which resets the gap detection
    expect($instance->hasGap())->toBeTrue();
})->with([
    'with one retry' => [[1]],
    'with two retries' => [[1, 2]],
    'with ten retries' => fn (): array => range(1, 10),
]);
