<?php

declare(strict_types=1);

namespace Storm\Projector\Filter;

use Storm\Chronicler\Direction;
use Storm\Contract\Chronicler\InMemoryQueryFilter;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\EventHeader;
use Storm\Contract\Projector\LoadLimiterProjectionQueryFilter;
use Storm\Contract\Projector\ProjectionQueryFilter;
use Storm\Contract\Projector\ProjectionQueryScope;
use Storm\Projector\Exception\InvalidArgumentException;

use function is_int;

final class InMemoryQueryScope implements ProjectionQueryScope
{
    public function fromIncludedPosition(): ProjectionQueryFilter
    {
        return new class() implements InMemoryQueryFilter, LoadLimiterProjectionQueryFilter
        {
            private int $streamPosition;

            private int $loadLimiter;

            private int $maxPosition;

            public function apply(): callable
            {
                $this->maxPosition = $this->loadLimiter <= 0 ? PHP_INT_MAX : $this->streamPosition + $this->loadLimiter;

                return function (DomainEvent $event): bool {
                    $eventPosition = $this->extractInternalPosition($event);

                    return $eventPosition >= $this->streamPosition && $eventPosition <= $this->maxPosition;
                };
            }

            public function setLoadLimiter(int $loadLimiter): void
            {
                $this->loadLimiter = $loadLimiter;
            }

            public function setStreamPosition(int $streamPosition): void
            {
                $this->streamPosition = $streamPosition;
            }

            public function orderBy(): Direction
            {
                return Direction::FORWARD;
            }

            private function extractInternalPosition(DomainEvent $event): int
            {
                $internalPosition = $event->header(EventHeader::INTERNAL_POSITION);

                if (! is_int($internalPosition) || $internalPosition < 1) {
                    throw new InvalidArgumentException('Internal position must be a positive integer');
                }

                return $internalPosition;
            }
        };
    }
}
