<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Repository\EventStream;

use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Projector\Repository\EventStream\DiscoverAllStream;

beforeEach(function () {
    $this->provider = $this->createMock(EventStreamProvider::class);
    $this->query = new DiscoverAllStream();
});

it('should return all streams without internal', function () {
    $this->provider->expects($this->once())
        ->method('allWithoutInternal')
        ->willReturn(['stream-1', 'stream-2']);

    $streams = ($this->query)($this->provider);

    expect($streams)->toBe(['stream-1', 'stream-2']);
});

it('should return empty array if no streams', function () {
    $this->provider->expects($this->once())
        ->method('allWithoutInternal')
        ->willReturn([]);

    $streams = ($this->query)($this->provider);

    expect($streams)->toBe([]);
});
