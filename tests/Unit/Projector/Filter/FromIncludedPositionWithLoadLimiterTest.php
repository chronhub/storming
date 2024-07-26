<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Filter;

use Illuminate\Database\Query\Builder;
use Storm\Contract\Projector\LoadLimiterProjectionQueryFilter;
use Storm\Contract\Projector\ProjectionQueryFilterConnection;
use Storm\Projector\Filter\FromIncludedPositionWithLoadLimiter;
use Storm\Projector\Filter\LoadLimiter;
use Storm\Stream\StreamPosition;

beforeEach(function () {
    $this->builder = mock(Builder::class);
    $this->filter = new FromIncludedPositionWithLoadLimiter();
});

test('default instance', function () {
    expect($this->filter)->toBeInstanceOf(ProjectionQueryFilterConnection::class)
        ->and($this->filter)->toBeInstanceOf(LoadLimiterProjectionQueryFilter::class);
});

test('callback', function (int $position, int $limit) {
    $streamPosition = new StreamPosition($position);
    $loadLimiter = new LoadLimiter($limit);

    $this->builder->expects('where')->with('position', '>=', $position)->andReturn($this->builder);
    $this->builder->expects('orderBy')->with('position')->andReturn($this->builder);
    $this->builder->expects('limit')->with($limit)->andReturn($this->builder);

    $this->filter->setStreamPosition($streamPosition);
    $this->filter->setLoadLimiter($loadLimiter);

    $this->filter->apply()($this->builder);
})
    ->with(['position' => [1, 2, 3, 10, 50, 1000]])
    ->with(['batch limit' => [100, 500, 1000]]);
