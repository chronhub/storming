<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Scope;

use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\QueryProjectorScope;
use Storm\Projector\Scope\QueryAccess;
use Storm\Projector\Workflow\Notification\Sprint\SprintStopped;
use Storm\Projector\Workflow\Notification\Stream\CurrentProcessedStream;

beforeEach(function () {
    $this->hub = mock(NotificationHub::class);
    $this->clock = mock(SystemClock::class);
    $this->access = new QueryAccess($this->hub, $this->clock);
});

test('default instance', function () {
    expect($this->access)->toBeInstanceOf(QueryProjectorScope::class);
});

it('stop projection', function () {
    $this->hub->expects('notify')
        ->withArgs(fn (string $e) => $e === SprintStopped::class);

    $this->access->stop();
});

it('get current processed stream name', function (string $streamName) {
    $this->hub->expects('expect')
        ->withArgs(fn (string $type) => $type === CurrentProcessedStream::class)
        ->andReturn($streamName);

    expect($this->access->streamName())->toBe($streamName);
})->with(['stream names' => ['stream-1', 'stream-2', 'stream-3']]);

it('get clock', function () {
    expect($this->access->clock())->toBe($this->clock);
});
