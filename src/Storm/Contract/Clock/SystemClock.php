<?php

declare(strict_types=1);

namespace Storm\Contract\Clock;

use DateTimeImmutable;
use Symfony\Component\Clock\ClockInterface;

interface SystemClock extends ClockInterface
{
    public function generate(): string;

    public function toDateTimeImmutable(string|DateTimeImmutable $pointInTime): DateTimeImmutable;

    public function format(string|DateTimeImmutable $pointInTime): string;

    public function getFormat(): string;
}
