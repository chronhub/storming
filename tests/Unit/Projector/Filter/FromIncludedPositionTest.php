<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Filter;

use Filter\DatabaseProjectionQueryFilter;
use Illuminate\Database\Query\Builder;
use Storm\Projector\Stream\Filter\FromIncludedPosition;
use Storm\Stream\StreamPosition;

beforeEach(function () {
    $this->builder = mock(Builder::class);
    $this->filter = new FromIncludedPosition();
});

test('default instance', function () {
    expect($this->filter)->toBeInstanceOf(DatabaseProjectionQueryFilter::class);
});

test('callback', function (int $position) {
    $this->builder->expects('where')->with('position', '>=', $position)->andReturn($this->builder);
    $this->builder->expects('orderBy')->with('position')->andReturn($this->builder);

    $this->filter->setStreamPosition(new StreamPosition($position));

    $this->filter->apply()($this->builder);
})->with(['position' => [1, 2, 3, 10, 50, 1000]]);
