<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Repository\EventStream;

use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Projector\Repository\EventStream\DiscoverAllStream;

beforeEach(function () {
    $this->provider = mock(EventStreamProvider::class);
    $this->query = new DiscoverAllStream();
});

test('return all streams without internal', function () {
    $this->provider
        ->shouldReceive('allWithoutInternal')
        ->andReturn(['stream-1', 'stream-2'])
        ->once();

    $streams = ($this->query)($this->provider);

    expect($streams)->toBe(['stream-1', 'stream-2']);
});

test('return empty array if no streams', function () {
    $this->provider
        ->shouldReceive('allWithoutInternal')
        ->andReturn([])
        ->once();

    $streams = ($this->query)($this->provider);

    expect($streams)->toBe([]);
});
