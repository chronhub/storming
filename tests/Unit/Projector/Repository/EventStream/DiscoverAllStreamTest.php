<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Repository\EventStream;

use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Projector\Stream\Query\DiscoverAllStream;

beforeEach(function () {
    $this->provider = mock(EventStreamProvider::class);
    $this->query = new DiscoverAllStream;
});

test('return all streams without internal', function (array $streams) {
    $this->provider->expects('all')->andReturn($streams);

    $streams = ($this->query)($this->provider);

    expect($streams)->toBe($streams);
})->with([[['stream1', 'stream2']], [['stream1', 'stream2', 'stream3']]]);

test('return empty array if no streams', function () {
    $this->provider->expects('all')->andReturn([]);

    $streams = ($this->query)($this->provider);

    expect($streams)->toBe([]);
});
