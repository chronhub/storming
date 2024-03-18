<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow;

use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Projector\LoadLimiterProjectionQueryFilter;
use Storm\Contract\Projector\ProjectionQueryFilter;
use Storm\Contract\Projector\StreamNameAwareQueryFilter;

class QueryFilterResolver
{
    public function __construct(private readonly QueryFilter $queryFilter)
    {
    }

    public function __invoke(string $streamName, int $nextPosition, int $limit): QueryFilter
    {
        $queryFilter = $this->queryFilter;

        if ($queryFilter instanceof StreamNameAwareQueryFilter) {
            $queryFilter->setStreamName($streamName);
        }

        if ($queryFilter instanceof LoadLimiterProjectionQueryFilter) {
            $queryFilter->setLoadLimiter($limit);
        }

        if ($queryFilter instanceof ProjectionQueryFilter) {
            $queryFilter->setStreamPosition($nextPosition);
        }

        return $queryFilter;
    }
}
