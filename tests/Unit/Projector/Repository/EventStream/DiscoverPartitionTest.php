<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Repository\EventStream;

use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Stream\Query\DiscoverPartition;

beforeEach(function () {
    $this->provider = mock(EventStreamProvider::class);
});

test('discover streams by categories', function () {
    $this->provider
        ->expects('filterByPartitions')
        ->with(['partition-1', 'partition-2'])
        ->andReturn(['partition-1', 'partition-2']);

    $query = new DiscoverPartition(['partition-1', 'partition-2']);
    $streams = $query($this->provider);

    expect($streams)->toBe(['partition-1', 'partition-2']);
});

test('raise exception if partition is empty', function () {
    new DiscoverPartition([]);
})->throws(InvalidArgumentException::class, 'Partition cannot be empty');

test('raise exception if categories contain duplicate', function () {
    new DiscoverPartition(['partition-1', 'partition-1']);
})->throws(InvalidArgumentException::class, 'Partition cannot contain duplicate');
