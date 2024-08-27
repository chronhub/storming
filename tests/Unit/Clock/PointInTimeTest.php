<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Clock;

use Carbon\CarbonImmutable;
use Storm\Clock\PointInTime;

test('create new instance', function () {
    $clock = PointInTime::now();

    expect($clock)->toBeInstanceOf(PointInTime::class)
        ->and($clock->carbon())->toBeInstanceOf(CarbonImmutable::class)
        ->and($clock::DATE_TIME_FORMAT)->toBe('Y-m-d\TH:i:s.u')
        ->and($clock::DATE_TIME_ZONE)->toBe('UTC')
        ->and($clock::DATE_TIME_PATTERN)->toBe('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}/');
});

test('create from string', function () {
    $clock = PointInTime::now();

    $time = PointInTime::fromString($clock->format());

    expect($time)->toEqual($clock)
        ->and($time)->not->toBe($clock)
        ->and($time->format())->toBe($clock->format());
});

test('modify', function () {
    $time = '2024-07-04T10:53:52.000000';
    $clock = PointInTime::fromString($time);

    $add1minute = $clock->modify(
        fn (CarbonImmutable $carbon): CarbonImmutable => $carbon->addMinute()
    );

    expect($clock)->not->toBe($add1minute)
        ->and($clock)->not->toEqual($add1minute)
        ->and($add1minute->format())->toBe('2024-07-04T10:54:52.000000')
        ->and($clock->carbon())->not->toBe($add1minute->carbon());
});

test('compare', function () {
    $time = '2024-07-04T10:53:52.969607';
    $clock = PointInTime::fromString($time);

    $add1ms = $clock->modify(
        fn (CarbonImmutable $carbon): CarbonImmutable => $carbon->addMillisecond()
    );

    expect($clock->compare(
        $add1ms,
        fn (CarbonImmutable $currentTime, PointInTime $time): bool => $currentTime->lessThan($time->carbon()))
    )->toBeTrue();
});

test('compare with string point in time and convert to point in time instance', function () {
    $time = '2024-07-04T10:53:52.969607';
    $clock = PointInTime::fromString($time);

    $result = $clock->compare($time, function (CarbonImmutable $currentTime, PointInTime $time): bool {
        return $time->format() === '2024-07-04T10:53:52.969607';
    });

    expect($result)->toBeTrue();
});

test('get timestamp in ms', function () {
    $time = '2024-07-04T10:53:52.969607';
    $clock = PointInTime::fromString($time);

    expect($clock->timestampMs())->toBe($clock->toDateTime()->format('U.u'));
});
