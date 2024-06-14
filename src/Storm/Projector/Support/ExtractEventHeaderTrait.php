<?php

declare(strict_types=1);

namespace Storm\Projector\Support;

use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\EventHeader;
use Storm\Projector\Exception\InvalidArgumentException;

use function is_int;

trait ExtractEventHeaderTrait
{
    /**
     * Extract internal position from event header
     *
     * @return positive-int
     *
     * @throws InvalidArgumentException when internal position is not a positive integer
     */
    protected function extractInternalPosition(DomainEvent $event): int
    {
        $internalPosition = $event->header(EventHeader::INTERNAL_POSITION);

        if (! is_int($internalPosition) || $internalPosition < 1) {
            throw new InvalidArgumentException('Internal position must be a positive integer');
        }

        return $internalPosition;
    }
}
