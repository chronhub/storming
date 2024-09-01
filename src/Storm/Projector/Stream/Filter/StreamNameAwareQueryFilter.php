<?php

declare(strict_types=1);

namespace Storm\Projector\Stream\Filter;

use Storm\Stream\StreamName;

interface StreamNameAwareQueryFilter extends ProjectionQueryFilter
{
    /**
     * Set the query filter to be aware of the stream name.
     */
    public function setStreamName(StreamName $streamName): void;
}
