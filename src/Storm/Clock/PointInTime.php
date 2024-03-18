<?php

declare(strict_types=1);

namespace Storm\Clock;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use DomainException;
use Exception;
use Storm\Contract\Clock\SystemClock;
use Symfony\Component\Clock\MonotonicClock;

use function is_string;
use function strtoupper;

final readonly class PointInTime implements SystemClock
{
    final public const DATE_TIME_FORMAT = 'Y-m-d\TH:i:s.u';

    private MonotonicClock $clock;

    private DateTimeZone $timezone;

    public function __construct()
    {
        $this->timezone = new DateTimeZone('UTC');
        $this->clock = new MonotonicClock($this->timezone);
    }

    public function sleep(float|int $seconds): void
    {
        $this->clock->sleep($seconds);
    }

    public function now(): DateTimeImmutable
    {
        return $this->clock->now();
    }

    public function toDateTimeImmutable(DateTimeImmutable|string $pointInTime): DateTimeImmutable
    {
        if ($pointInTime instanceof DateTimeImmutable) {
            return $pointInTime;
        }

        try {
            return new DateTimeImmutable($pointInTime, $this->timezone);
        } catch (Exception $e) {
            throw new DomainException("Invalid point in time format: $pointInTime", 0, $e);
        }
    }

    public function generate(): string
    {
        return $this->now()->format(self::DATE_TIME_FORMAT);
    }

    public function format(DateTimeImmutable|string $pointInTime): string
    {
        if (is_string($pointInTime)) {
            return $pointInTime;
        }

        return $pointInTime->format(self::DATE_TIME_FORMAT);
    }

    public function getFormat(): string
    {
        return self::DATE_TIME_FORMAT;
    }

    public function isGreaterThan(DateTimeImmutable|string $pointInTime, DateTimeImmutable|string $anotherPointInTime): bool
    {
        return $this->toDateTimeImmutable($pointInTime) > $this->toDateTimeImmutable($anotherPointInTime);
    }

    public function isGreaterThanNow(DateTimeImmutable|string $pointInTime): bool
    {
        return $this->now() < $this->toDateTimeImmutable($pointInTime);
    }

    public function isNowSubGreaterThan(DateInterval|string $interval, DateTimeImmutable|string $pointInTime): bool
    {
        if (is_string($interval)) {
            $interval = new DateInterval(strtoupper($interval));
        }

        return $this->now()->sub($interval) > $this->toDateTimeImmutable($pointInTime);
    }

    public function withTimeZone(DateTimeZone|string $timezone): static
    {
        throw new DomainException('UTC timezone is only supported');
    }
}
