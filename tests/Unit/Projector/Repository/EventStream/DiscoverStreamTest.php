<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Repository\EventStream;

use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Repository\EventStream\DiscoverStream;

beforeEach(function () {
    $this->provider = mock(EventStreamProvider::class);
});

it('return streams by names', function () {
    $this->provider
        ->shouldReceive('filterByStreams')
        ->with(['stream1', 'stream2'])
        ->andReturn(['stream1', 'stream2'])
        ->once();

    $query = new DiscoverStream(['stream1', 'stream2']);
    $streams = $query($this->provider);

    expect($streams)->toBe(['stream1', 'stream2']);
});

it('raise exception if streams is empty', function () {
    new DiscoverStream([]);
})->throws(InvalidArgumentException::class, 'Streams cannot be empty');

it('raise exception if streams contain duplicate', function (array $streams) {
    new DiscoverStream(...$streams);
})
    ->with([
        ['duplicate stream names' => [['stream1', 'stream1']]],
        ['duplicate stream names 2' => [['stream1', 'stream2', 'stream1']]],
    ])
    ->throws(InvalidArgumentException::class, 'Streams cannot contain duplicate');
