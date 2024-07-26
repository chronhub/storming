<?php

declare(strict_types=1);

namespace Storm\Clock;

use Storm\Contract\Clock\SystemClock;
use Storm\Projector\Exception\InvalidArgumentException;

class ClockFactory
{
    public static function create(): SystemClock
    {
        return new Clock();
    }

    public static function createPointInTimeNormalizer(): PointInTimeNormalizer
    {
        return new PointInTimeNormalizer();
    }

    /**
     * Generate point in time as string or object.
     *
     * @return array<string|PointInTime>
     */
    public static function times(int $times = 2, bool $asObject = true): array
    {
        if ($times < 1) {
            throw new InvalidArgumentException('Times must be greater than 0.');
        }

        $pointInTimes = [];

        for ($i = 0; $i < $times; $i++) {
            $pointInTimes[] = $asObject ? self::create()->now() : self::create()->generate();
        }

        return $pointInTimes;
    }
}
