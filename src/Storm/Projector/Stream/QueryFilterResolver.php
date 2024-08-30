<?php

declare(strict_types=1);

namespace Storm\Projector\Stream;

use Storm\Contract\Chronicler\QueryFilter;
use Storm\Projector\Stream\Filter\LoadLimiter;
use Storm\Projector\Stream\Filter\LoadLimiterQueryFilter;
use Storm\Projector\Stream\Filter\ProjectionQueryFilter;
use Storm\Projector\Stream\Filter\StreamNameAwareQueryFilter;
use Storm\Stream\StreamPosition;

final readonly class QueryFilterResolver
{
    public function __construct(private QueryFilter $queryFilter) {}

    // fixMe Load limiter should be optional,as it could be inconsistent between the option
    //  and his implementation
    //  allow null in projection options
    public function __invoke(string $streamName, StreamPosition $streamPosition, LoadLimiter $loadLimiter): QueryFilter
    {
        $queryFilter = $this->queryFilter;

        if ($queryFilter instanceof StreamNameAwareQueryFilter) {
            $queryFilter->setStreamName($streamName);
        }

        if ($queryFilter instanceof LoadLimiterQueryFilter) {
            // todo raise exception if loadLimiter is null
            $queryFilter->setLoadLimiter($loadLimiter);
        }

        if ($queryFilter instanceof ProjectionQueryFilter) {
            $queryFilter->setStreamPosition($streamPosition);
        }

        return $queryFilter;
    }
}
