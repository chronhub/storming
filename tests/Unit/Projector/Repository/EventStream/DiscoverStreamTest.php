<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Repository\EventStream;

use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Repository\EventStream\DiscoverStream;

beforeEach(function () {
    $this->provider = $this->createMock(EventStreamProvider::class);
});

it('return streams by names', function () {
    $this->provider->expects($this->once())
        ->method('filterByStreams')
        ->with(['stream-1', 'stream-2'])
        ->willReturn(['stream-1', 'stream-2']);

    $query = new DiscoverStream(['stream-1', 'stream-2']);
    $streams = $query($this->provider);

    expect($streams)->toBe(['stream-1', 'stream-2']);
});

it('raise exception if streams is empty', function () {
    new DiscoverStream([]);
})->throws(InvalidArgumentException::class, 'Streams cannot be empty');

it('raise exception if streams contain duplicate', function () {
    new DiscoverStream(['stream-1', 'stream-1']);
})->throws(InvalidArgumentException::class, 'Streams cannot contain duplicate');
