<?php

declare(strict_types=1);

namespace Storm\Clock;

use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Closure;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Carbon;

use function is_string;
use function preg_match;

final readonly class PointInTime
{
    final public const string DATE_TIME_FORMAT = 'Y-m-d\TH:i:s.u';

    final public const string DATE_TIME_PATTERN = '/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}/';

    final public const string DATE_TIME_ZONE = 'UTC';

    private function __construct(private CarbonImmutable $carbon) {}

    /**
     * Create a new point in time from the current time.
     */
    public static function now(): self
    {
        $carbon = Carbon::now(self::timeZone());

        return new self($carbon->toImmutable());
    }

    /**
     * Create a new point in time from the current time as a string.
     */
    public static function generate(): string
    {
        return self::now()->carbon->format(self::DATE_TIME_FORMAT);
    }

    /**
     * Create a new point in time from a string.
     *
     * @throws InvalidFormatException
     */
    public static function fromString(string $dateTime): self
    {
        $carbon = Carbon::createFromFormat(self::DATE_TIME_FORMAT, $dateTime, self::timeZone());

        return new self($carbon->toImmutable());
    }

    public function isEqualTo(PointInTime|string $pointInTime): bool
    {
        if (is_string($pointInTime)) {
            $pointInTime = self::fromString($pointInTime);
        }

        return $this->carbon->eq($pointInTime->carbon);
    }

    /**
     * Check if the current point in time is greater than the given point in time.
     */
    public function isGreaterThan(PointInTime|string $pointInTime): bool
    {
        if (is_string($pointInTime)) {
            $pointInTime = self::fromString($pointInTime);
        }

        return $this->carbon->gt($pointInTime->carbon);
    }

    /**
     * Check if the current point in time is less than the given point in time.
     */
    public function isLessThan(PointInTime|string $pointInTime): bool
    {
        if (is_string($pointInTime)) {
            $pointInTime = self::fromString($pointInTime);
        }

        return $this->carbon->lt($pointInTime->carbon);
    }

    /**
     * Add a time interval from the current point in time.
     *
     * @see CarbonImmutable::add()
     */
    public function add(string|DateInterval $interval, int|float $unit = 1): self
    {
        return new self($this->carbon->add($interval, $unit));
    }

    /**
     * Subtract a time interval from the current point in time.
     *
     * @see CarbonImmutable::sub()
     */
    public function sub(string|DateInterval $interval, int|float $unit = 1): self
    {
        return new self($this->carbon->sub($interval, $unit));
    }

    /**
     * Modify the current point in time and return a new instance.
     *
     * @template T of mixed
     *
     * @param Closure(CarbonImmutable, T): CarbonImmutable $callback
     */
    public function modify(Closure $callback, mixed ...$arguments): self
    {
        $carbon = $callback($this->carbon, ...$arguments);

        return new self($carbon);
    }

    /**
     * Compare the current point in time with another point in time.
     *
     * @param Closure(CarbonImmutable, PointInTime): bool $callback
     */
    public function compare(self|string $pointInTime, Closure $callback): bool
    {
        if (is_string($pointInTime)) {
            $pointInTime = self::fromString($pointInTime);
        }

        return $callback($this->carbon, $pointInTime);
    }

    /**
     * Format the point in time.
     */
    public function format(): string
    {
        return $this->carbon->format(self::DATE_TIME_FORMAT);
    }

    /**
     * Match the date time string with the pattern.
     */
    public static function match(string $dateTime): bool
    {
        return preg_match(self::DATE_TIME_PATTERN, $dateTime) === 1;
    }

    /**
     * Return a clone instance of carbon immutable.
     */
    public function carbon(): CarbonImmutable
    {
        return clone $this->carbon;
    }

    /**
     * Return a DateTimeImmutable instance.
     */
    public function toDateTime(): DateTimeImmutable
    {
        return $this->carbon->toDateTimeImmutable();
    }

    /**
     * Return the timestamp in "seconds" format.
     */
    public function timestamp(): int
    {
        return $this->toDateTime()->getTimestamp();
    }

    /**
     * Return the timestamp in "seconds.microseconds" format.
     */
    public function timestampMs(): string
    {
        return $this->carbon->format('U.u');
    }

    /**
     * Return the time zone.
     */
    public static function timeZone(): DateTimeZone
    {
        return new DateTimeZone(self::DATE_TIME_ZONE);
    }
}
