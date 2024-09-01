<?php

declare(strict_types=1);

namespace Storm\Projector\Stream;

use Storm\Contract\Chronicler\QueryFilter;
use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Stream\Filter\LoadLimiter;
use Storm\Projector\Stream\Filter\LoadLimiterQueryFilter;
use Storm\Projector\Stream\Filter\ProjectionQueryFilter;
use Storm\Projector\Stream\Filter\StreamNameAwareQueryFilter;
use Storm\Stream\StreamPosition;

final readonly class QueryFilterResolver
{
    public function __construct(private QueryFilter $queryFilter) {}

    public function __invoke(string $streamName, StreamPosition $streamPosition, ?LoadLimiter $loadLimiter = null): QueryFilter
    {
        $queryFilter = $this->queryFilter;

        if ($queryFilter instanceof StreamNameAwareQueryFilter) {
            $queryFilter->setStreamName($streamName);
        }

        if ($queryFilter instanceof LoadLimiterQueryFilter) {
            if ($loadLimiter === null) {
                throw new InvalidArgumentException('Query filter implements a load limiter contract, therefore, no load limiter provided');
            }

            $queryFilter->setLoadLimiter($loadLimiter);
        }

        if ($queryFilter instanceof ProjectionQueryFilter) {
            $queryFilter->setStreamPosition($streamPosition);
        }

        return $queryFilter;
    }
}
