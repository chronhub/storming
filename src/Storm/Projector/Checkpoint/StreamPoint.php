<?php

declare(strict_types=1);

namespace Storm\Projector\Checkpoint;

use Storm\Clock\PointInTime;
use Storm\Stream\StreamPosition;

/**
 * Stream point is a point in a stream which is currently being processed.
 */
final readonly class StreamPoint
{
    public function __construct(
        public string $name,
        public StreamPosition $position,
        public string|PointInTime $eventTime,
    ) {}
}
