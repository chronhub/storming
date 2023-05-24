<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Clock;

use DateTimeImmutable;
use DateTimeZone;
use DomainException;
use Storm\Clock\PointInTime;
use Storm\Contract\Clock\SystemClock;

it('create point in time instance', function () {
    $pointInTime = new PointInTime();

    expect($pointInTime)->toBeInstanceOf(SystemClock::class);

    $now = $pointInTime->now();

    expect($now)
        ->toBeInstanceOf(DateTimeImmutable::class)
        ->and($now->getTimezone()->getName())->toBe('UTC');
});

it('create point in time from string to date time immutable', function () {
    $pointInTime = new PointInTime();

    $now = $pointInTime->now();

    $pointInTime = $pointInTime->toDateTimeImmutable($now->format(PointInTime::DATE_TIME_FORMAT));

    expect($pointInTime)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($pointInTime)->toEqual($now);
});

it('return same instance to date time immutable with date time', function () {
    $pointInTime = new PointInTime();

    $now = $pointInTime->now();

    $pointInTime = $pointInTime->toDateTimeImmutable($now);

    expect($pointInTime)->toBe($now);
});

it('generate string date time', function (string $pattern) {
    $pointInTime = new PointInTime();

    $pointInTime = $pointInTime->generate();

    expect($pointInTime)->toBeString()->toMatch($pattern);
})->with('formatPattern');

it('test sleep', function () {
    $pointInTime = new PointInTime();

    $now = $pointInTime->now();

    $pointInTime->sleep(0.100);

    $nowPlus = $pointInTime->now();

    expect($nowPlus)->toBeInstanceOf(DateTimeImmutable::class)->toBeGreaterThan($now);
});

it('format given date time instance', function (string $pattern) {
    $pointInTime = new PointInTime();

    $now = $pointInTime->now();

    $pointInTime = $pointInTime->format($now);

    expect($pointInTime)->toBeString()->toMatch($pattern);
})->with('formatPattern');

it('return format given date time string', function (string $pattern) {
    $pointInTime = new PointInTime();

    $now = $pointInTime->now()->format($pointInTime->getFormat());

    $pointInTime = $pointInTime->format($now);

    expect($pointInTime)->toBeString()->toBe($now)->toMatch($pattern);
})->with('formatPattern');

it('assert format', function () {
    $pointInTime = new PointInTime();

    expect($pointInTime->getFormat())
        ->toBe(PointInTime::DATE_TIME_FORMAT)
        ->toBe('Y-m-d\TH:i:s.u');
});

it('raise exception with invalid date time given', function () {
    $pointInTime = new PointInTime();

    $pointInTime->toDateTimeImmutable('invalid-date-time');
})->throws(DomainException::class);

it('raise exception when changing timezone', function () {
    $pointInTime = new PointInTime();

    $pointInTime->withTimeZone(new DateTimeZone('America/Sao_Paulo'));
})->throws(DomainException::class, 'UTC timezone is only supported');

dataset('formatPattern', ['/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d+$/']);
