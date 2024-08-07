<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow;

use Filter\ProjectionQueryFilter;
use Filter\StreamNameAwareQueryFilter;
use Storm\Contract\Projector\LoadLimiterProjectionQueryFilter;
use Storm\Projector\Stream\Filter\LoadLimiter;
use Storm\Projector\Stream\QueryFilterResolver;
use Storm\Stream\StreamPosition;

test('set projection query filter', function () {
    $streamPosition = new StreamPosition(1);
    $queryFilter = mock(ProjectionQueryFilter::class);
    $queryFilter->expects('setStreamPosition')->with($streamPosition);

    $resolver = new QueryFilterResolver($queryFilter);
    $instance = $resolver('stream', $streamPosition, new LoadLimiter(0));

    expect($instance)->toBe($queryFilter);
});

test('set stream name aware query filter', function () {
    $streamPosition = new StreamPosition(1);

    $queryFilter = mock(StreamNameAwareQueryFilter::class);
    $queryFilter->expects('setStreamName')->with('stream1');
    $queryFilter->expects('setStreamPosition')->with($streamPosition);

    $resolver = new QueryFilterResolver($queryFilter);
    $instance = $resolver('stream1', $streamPosition, new LoadLimiter(0));

    expect($instance)->toBe($queryFilter);
});

test('set load limiter projection query filter', function (int $position, int $loadLimiter) {
    $streamPosition = new StreamPosition($position);
    $loadLimiter = new LoadLimiter($loadLimiter);

    $queryFilter = mock(LoadLimiterProjectionQueryFilter::class);
    $queryFilter->expects('setStreamPosition')->with($streamPosition);
    $queryFilter->expects('setLoadLimiter')->with($loadLimiter);

    $resolver = new QueryFilterResolver($queryFilter);
    $instance = $resolver('stream1', $streamPosition, $loadLimiter);

    expect($instance)->toBe($queryFilter);
})
    ->with([[1], [2], [3], [10], [50], [1000]])
    ->with([[10], [100], [1000]]);

test('set load limiter projection query filter with zero value and return php int max', function (int $position) {
    $streamPosition = new StreamPosition($position);
    $loadLimiter = new LoadLimiter(0);

    expect($loadLimiter->value)->toBe(PHP_INT_MAX);

    $queryFilter = mock(LoadLimiterProjectionQueryFilter::class);
    $queryFilter->expects('setStreamPosition')->with($streamPosition);
    $queryFilter->expects('setLoadLimiter')->with($loadLimiter);

    $resolver = new QueryFilterResolver($queryFilter);
    $instance = $resolver('stream1', $streamPosition, $loadLimiter);

    expect($instance)->toBe($queryFilter);
})->with([[1], [2], [3], [10], [50], [1000]]);
