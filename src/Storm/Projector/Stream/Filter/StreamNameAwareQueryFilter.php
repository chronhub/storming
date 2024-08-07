<?php

declare(strict_types=1);

namespace Storm\Projector\Stream\Filter;

interface StreamNameAwareQueryFilter extends ProjectionQueryFilter
{
    /**
     * Set the query filter to be aware of the stream name.
     */
    public function setStreamName(string $streamName): void;
}
