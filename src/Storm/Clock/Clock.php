<?php

declare(strict_types=1);

namespace Storm\Clock;

use DateTimeZone;
use Storm\Contract\Clock\SystemClock;

use function is_string;

final class Clock implements SystemClock
{
    public function now(): PointInTime
    {
        return PointInTime::now();
    }

    public function generate(): string
    {
        return PointInTime::generate();
    }

    public function from(string|PointInTime $pointInTime): PointInTime
    {
        if (is_string($pointInTime)) {
            return PointInTime::fromString($pointInTime);
        }

        return $pointInTime;
    }

    public function fromString(string $dateTime): PointInTime
    {
        return PointInTime::fromString($dateTime);
    }

    public function isValid(string $dateTime): bool
    {
        return PointInTime::match($dateTime);
    }

    public function getTimezone(): DateTimeZone
    {
        return PointInTime::timeZone();
    }

    public function getFormat(): string
    {
        return PointInTime::DATE_TIME_FORMAT;
    }
}
