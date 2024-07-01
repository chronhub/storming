<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow;

use Storm\Contract\Projector\LoadLimiterProjectionQueryFilter;
use Storm\Contract\Projector\ProjectionQueryFilter;
use Storm\Contract\Projector\StreamNameAwareQueryFilter;
use Storm\Projector\Workflow\QueryFilterResolver;

it('set projection query filter', function () {
    $queryFilter = mock(ProjectionQueryFilter::class);
    $queryFilter->expects('setStreamPosition')->with(1)->once();

    $resolver = new QueryFilterResolver($queryFilter);
    $instance = $resolver('stream', 1, 0);

    expect($instance)->toBe($queryFilter);
});

it('set stream name aware query filter', function () {
    $queryFilter = mock(StreamNameAwareQueryFilter::class);
    $queryFilter->expects('setStreamName')->with('stream')->once();
    $queryFilter->expects('setStreamPosition')->with(5)->once();

    $resolver = new QueryFilterResolver($queryFilter);
    $instance = $resolver('stream', 5, 0);

    expect($instance)->toBe($queryFilter);
});

it('set load limiter projection query filter', function () {
    $queryFilter = mock(LoadLimiterProjectionQueryFilter::class);
    $queryFilter->expects('setStreamPosition')->with(10)->once();
    $queryFilter->expects('setLoadLimiter')->with(100)->once();

    $resolver = new QueryFilterResolver($queryFilter);
    $instance = $resolver('stream', 10, 100);

    expect($instance)->toBe($queryFilter);
});
