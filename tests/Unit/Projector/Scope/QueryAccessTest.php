<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Scope;

use Scope\QueryProjectorScope;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Scope\QueryAccess;
use Storm\Projector\Workflow\Notification\Command\SprintStopped;
use Storm\Projector\Workflow\Notification\Promise\CurrentProcessedStream;

beforeEach(function () {
    $this->hub = mock(NotificationHub::class);
    $this->clock = mock(SystemClock::class);
    $this->access = new QueryAccess($this->hub, $this->clock);
});

test('default instance', function () {
    expect($this->access)->toBeInstanceOf(QueryProjectorScope::class);
});

test('stop projection', function () {
    $this->hub
        ->expects('emit')
        ->withArgs(fn (string $notification) => $notification === SprintStopped::class);

    $this->access->stop();
});

test('get current processed stream name', function (string $streamName) {
    $this->hub
        ->expects('await')
        ->withArgs(fn (string $notification) => $notification === CurrentProcessedStream::class)
        ->andReturn($streamName);

    expect($this->access->streamName())->toBe($streamName);
})->with(['stream names' => ['stream1', 'stream2', 'stream3']]);

test('get clock', function () {
    expect($this->access->clock())->toBe($this->clock);
});
