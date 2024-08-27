<?php

declare(strict_types=1);

namespace Storm\Contract\Clock;

use DateTimeZone;
use InvalidArgumentException;
use Storm\Clock\PointInTime;

interface SystemClock
{
    /**
     * Get the current point in time.
     */
    public function now(): PointInTime;

    /**
     * Return point in time from string or object.
     *
     * @throws InvalidArgumentException when string is not a valid point in time
     */
    public function from(string|PointInTime $pointInTime): PointInTime;

    /**
     * Return point in time from string.
     *
     * @throws InvalidArgumentException when string is not a valid point in time
     */
    public function fromString(string $dateTime): PointInTime;

    /**
     * Return string representation of the current point in time.
     */
    public function generate(): string;

    /**
     * Check if the given string is a valid point in time.
     */
    public function isValid(string $dateTime): bool;

    /**
     * Get the format of the point in time.
     */
    public function getFormat(): string;

    /**
     * Get the timezone of the system clock.
     */
    public function getTimezone(): DateTimeZone;
}
