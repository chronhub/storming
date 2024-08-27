<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Clock;

use Carbon\CarbonInterval;
use DateInterval;
use DateTimeZone;
use Storm\Clock\Clock;
use Storm\Clock\PointInTime;

beforeEach(function () {
    $this->clock = new Clock;
});

test('return current point in time', function () {
    $pointInTime = $this->clock->now();

    expect($pointInTime)->toBeInstanceOf(PointInTime::class);
});

test('return point in time from string', function () {
    $dateTime = '2021-08-01T00:00:00.000000';
    $pointInTime = $this->clock->fromString($dateTime);

    expect($pointInTime)->toBeInstanceOf(PointInTime::class)
        ->and($pointInTime->format())->toBe($dateTime);
});

test('return point int time from', function (string|PointInTime $pointInTime) {
    $expectedPointInTime = $this->clock->from($pointInTime);

    expect($expectedPointInTime)->toBeInstanceOf(PointInTime::class);

    if ($pointInTime instanceof PointInTime) {
        expect($expectedPointInTime)->toBe($pointInTime);
    }
})->with([
    ['string point in time' => '2021-08-01T00:00:00.000000'],
    ['point in time object' => fn () => PointInTime::fromString('2021-08-01T00:00:00.000000')],
]);

test('generate point in time', function () {
    $dateTime = $this->clock->generate();

    expect($dateTime)->toBeString()
        ->toMatch(PointInTime::DATE_TIME_PATTERN);
});

test('match point in time', function () {
    $result = $this->clock->isValid('2021-08-01T00:00:00.000000');

    expect($result)->toBeTrue();
});

test('date time string does not match pattern', function (string $time) {
    expect($this->clock->isValid($time))->toBeFalse();
})
    ->with([
        ['2024-07-04'],
        ['2024-07-04 12'],
        ['2024-07-04 12:10'],
        ['2024-07-04 12:10:10'],
        ['2024-07-04T10'],
        ['2024-07-04T10:37'],
        ['2024-07-04T10:37:56'],
        ['2024-07-04T10:37:56.8'],
        ['2024-07-04T10:37:56.81'],
        ['2024-07-04T10:37:56.811'],
        ['2024-07-04T10:37:56.8111'],
        ['2024-07-04T10:37:56.81111'],
    ]);

test('get time zone', function () {
    $timeZone = $this->clock->getTimezone();

    expect($timeZone)->toBeInstanceOf(DateTimeZone::class)
        ->and($timeZone->getName())->toBe('UTC');
});

test('get date time format', function () {
    $format = $this->clock->getFormat();

    expect($format)->toBe(PointInTime::DATE_TIME_FORMAT);
});

test('greater than', function (PointInTime|string $pointInTime) {
    expect($this->clock->now()->isGreaterThan($pointInTime))->toBeTrue();
})->with([
    ['2021-08-01T00:00:00.000000'],
    ['2022-08-01T00:00:01.000000'],
    ['2023-08-01T00:00:02.000000'],
    [fn () => PointInTime::fromString('2024-07-01T00:00:03.000000')],
]);

test('less than', function (PointInTime|string $pointInTime) {
    $pastTime = PointInTime::fromString('2020-07-01T00:00:03.000000');
    expect($pastTime->isLessThan($pointInTime))->toBeTrue();
})->with([
    ['2022-07-01T00:00:03.000000'],
    ['2023-07-01T00:00:03.000000'],
    [fn () => PointInTime::fromString('2022-07-01T00:00:03.000000')],
    [fn () => PointInTime::fromString('2023-07-01T00:00:03.000000')],
    [fn () => PointInTime::fromString('2024-07-01T00:00:03.000000')],
]);

test('equal to', function (PointInTime|string $pointInTime, bool $expected) {
    $time = PointInTime::fromString('2024-08-01T00:00:00.000000');
    expect($time->isEqualTo($pointInTime))->toBe($expected);
})->with([
    ['2021-08-01T00:00:00.000000', false],
    ['2022-08-01T00:00:01.000000', false],
    ['2023-08-01T00:00:02.000000', false],
    ['2024-08-01T00:00:00.000000', true],
    [fn () => PointInTime::fromString('2024-08-01T00:00:00.000000'), true],
]);

test('add unit', function (string|DateInterval $interval, int|string $unit, string $expected) {
    $time = PointInTime::fromString('2024-08-01T00:00:00.000000');
    expect($time->add($interval, $unit)->format())->toBe($expected);
})->with([
    ['milliseconds', 1000, '2024-08-01T00:00:01.000000'],
    [CarbonInterval::milliseconds(1000), 1, '2024-08-01T00:00:01.000000'],
    ['day', 1, '2024-08-02T00:00:00.000000'],
]);

test('subtract unit', function (string|DateInterval $interval, int|string $unit, string $expected) {
    $time = PointInTime::fromString('2024-08-01T00:00:00.000000');
    expect($time->sub($interval, $unit)->format())->toBe($expected);
})->with([
    ['milliseconds', 1000, '2024-07-31T23:59:59.000000'],
    [CarbonInterval::milliseconds(1000), 1, '2024-07-31T23:59:59.000000'],
    ['day', 1, '2024-07-31T00:00:00.000000'],
]);
