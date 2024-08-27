<?php

declare(strict_types=1);

namespace Storm\Projector\Support;

use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\EventHeader;
use Storm\Stream\StreamPosition;

trait ExtractEventHeaderTrait
{
    /**
     * Extract internal position from event header.
     */
    protected function extractInternalPosition(DomainEvent $event): StreamPosition
    {
        $internalPosition = $event->header(EventHeader::INTERNAL_POSITION);

        return StreamPosition::fromValue($internalPosition);
    }
}
