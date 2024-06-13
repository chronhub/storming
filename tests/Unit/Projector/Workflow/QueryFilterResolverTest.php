<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow;

use Storm\Contract\Projector\LoadLimiterProjectionQueryFilter;
use Storm\Contract\Projector\ProjectionQueryFilter;
use Storm\Contract\Projector\StreamNameAwareQueryFilter;
use Storm\Projector\Workflow\QueryFilterResolver;

it('set projection query filter', function () {
    $queryFilter = $this->createMock(ProjectionQueryFilter::class);
    $queryFilter->expects($this->once())->method('setStreamPosition')->with(1);

    $resolver = new QueryFilterResolver($queryFilter);
    $instance = $resolver('stream', 1, 0);

    expect($instance)->toBe($queryFilter);
});

it('set stream name aware query filter', function () {
    $queryFilter = $this->createMock(StreamNameAwareQueryFilter::class);
    $queryFilter->expects($this->once())->method('setStreamName')->with('stream');
    $queryFilter->expects($this->once())->method('setStreamPosition')->with(5);

    $resolver = new QueryFilterResolver($queryFilter);
    $instance = $resolver('stream', 5, 0);

    expect($instance)->toBe($queryFilter);
});

it('set load limiter projection query filter', function () {
    $queryFilter = $this->createMock(LoadLimiterProjectionQueryFilter::class);
    $queryFilter->expects($this->once())->method('setStreamPosition')->with(10);
    $queryFilter->expects($this->once())->method('setLoadLimiter')->with(100);

    $resolver = new QueryFilterResolver($queryFilter);
    $instance = $resolver('stream', 10, 100);

    expect($instance)->toBe($queryFilter);
});
