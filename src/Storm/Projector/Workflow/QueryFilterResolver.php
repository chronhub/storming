<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow;

use Storm\Contract\Chronicler\QueryFilter;
use Storm\Projector\Filter\LoadLimiter;
use Storm\Projector\Filter\LoadLimiterQueryFilter;
use Storm\Projector\Filter\ProjectionQueryFilter;
use Storm\Projector\Filter\StreamNameAwareQueryFilter;
use Storm\Stream\StreamPosition;

final readonly class QueryFilterResolver
{
    public function __construct(private QueryFilter $queryFilter) {}

    public function __invoke(string $streamName, StreamPosition $streamPosition, LoadLimiter $loadLimiter): QueryFilter
    {
        $queryFilter = $this->queryFilter;

        if ($queryFilter instanceof StreamNameAwareQueryFilter) {
            $queryFilter->setStreamName($streamName);
        }

        if ($queryFilter instanceof LoadLimiterQueryFilter) {
            $queryFilter->setLoadLimiter($loadLimiter);
        }

        if ($queryFilter instanceof ProjectionQueryFilter) {
            $queryFilter->setStreamPosition($streamPosition);
        }

        return $queryFilter;
    }
}
