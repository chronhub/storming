<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Clock;

use Storm\Clock\Clock;
use Storm\Clock\ClockFactory;
use Storm\Clock\PointInTime;
use Storm\Clock\PointInTimeNormalizer;
use Storm\Projector\Exception\InvalidArgumentException;

beforeEach(function () {
    $this->factory = new ClockFactory();
});

test('return instance of clock', function () {
    $clock = $this->factory->create();

    expect($clock)->toBeInstanceOf(Clock::class);
});

test('return instance of point in time normalizer', function () {
    $pointInTimeNormalizer = $this->factory->createPointInTimeNormalizer();

    expect($pointInTimeNormalizer)->toBeInstanceOf(PointInTimeNormalizer::class);
});

test('times must be greater than 0', function () {
    $this->factory::times(0);
})->throws(InvalidArgumentException::class, 'Times must be greater than 0.');

test('return point in times as array of objects', function (int $times) {
    $pointInTimes = $this->factory::times($times);

    expect($pointInTimes)->toBeArray()
        ->and($pointInTimes)->each->toBeInstanceOf(PointInTime::class)
        ->and($pointInTimes)->toHaveCount($times);
})
    ->with(['times' => [1], [2], [5]]);

test('return point in times as array of strings', function (int $times) {
    $pointInTimes = $this->factory::times($times, false);

    expect($pointInTimes)->toBeArray()
        ->and($pointInTimes)->each->toBeString()
        ->and($pointInTimes)->each->toMatch(PointInTime::DATE_TIME_PATTERN)
        ->and($pointInTimes)->toHaveCount($times);
})->with(['times' => [5], [10], [20]]);
