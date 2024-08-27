<?php

declare(strict_types=1);

namespace Storm\Support\Facade;

use DateTimeZone;
use Illuminate\Support\Facades\Facade;
use Storm\Clock\PointInTime;

/**
 * @method static PointInTime  now()
 * @method static PointInTime  from(string|PointInTime $pointInTime)
 * @method static PointInTime  fromString(string $pointInTime)
 * @method static string       generate()
 * @method static bool         isValid(string $pointInTime)
 * @method static string       getFormat()
 * @method static DateTimeZone getTimezone()
 */
class Clock extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'storm.clock';
    }
}
