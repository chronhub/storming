<?php

declare(strict_types=1);

namespace Storm\Contract\Clock;

use DateInterval;
use DateTimeImmutable;
use Symfony\Component\Clock\ClockInterface;

interface SystemClock extends ClockInterface
{
    public function generate(): string;

    public function toDateTimeImmutable(string|DateTimeImmutable $pointInTime): DateTimeImmutable;

    public function format(string|DateTimeImmutable $pointInTime): string;

    public function getFormat(): string;

    public function isGreaterThan(DateTimeImmutable|string $pointInTime, DateTimeImmutable|string $anotherPointInTime): bool;

    public function isGreaterThanNow(DateTimeImmutable|string $pointInTime): bool;

    public function isNowSubGreaterThan(DateInterval|string $interval, DateTimeImmutable|string $pointInTime): bool;
}
