<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

interface StreamNameAwareQueryFilter extends ProjectionQueryFilter
{
    /**
     * Set the stream name to filter.
     */
    public function setStreamName(string $streamName): void;
}
