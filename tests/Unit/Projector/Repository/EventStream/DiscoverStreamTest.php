<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Repository\EventStream;

use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Stream\Query\DiscoverStream;

beforeEach(function () {
    $this->provider = mock(EventStreamProvider::class);
});

test('return streams by names', function () {
    $this->provider
        ->expects('filterByStreams')
        ->with(['stream1', 'stream2'])
        ->andReturn(['stream1', 'stream2']);

    $query = new DiscoverStream(['stream1', 'stream2']);
    $streams = $query($this->provider);

    expect($streams)->toBe(['stream1', 'stream2']);
});

test('raise exception if streams is empty', function () {
    new DiscoverStream([]);
})->throws(InvalidArgumentException::class, 'Streams cannot be empty');

test('raise exception if streams contain duplicate', function (array $streams) {
    new DiscoverStream(...$streams);
})
    ->with([
        ['duplicate stream names' => [['stream1', 'stream1']]],
        ['duplicate stream names 2' => [['stream1', 'stream2', 'stream1']]],
    ])
    ->throws(InvalidArgumentException::class, 'Streams cannot contain duplicate');
