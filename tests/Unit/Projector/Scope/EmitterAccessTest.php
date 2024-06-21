<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Scope;

use ArrayAccess;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Projector\EmitterScope;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Scope\EmitterAccess;
use Storm\Projector\Workflow\Notification\Management\EventEmitted;
use Storm\Projector\Workflow\Notification\Management\EventLinkedTo;
use Storm\Projector\Workflow\Notification\Management\ProjectionClosed;
use Storm\Projector\Workflow\Notification\Stream\CurrentProcessedStream;

beforeEach(function () {
    $this->hub = mock(NotificationHub::class);
    $this->clock = mock(SystemClock::class);
    $this->access = new EmitterAccess($this->hub, $this->clock);
});

dataset('stream names', ['stream-1', 'stream-2', 'stream-3']);

test('default instance', function () {
    expect($this->access)->toBeInstanceOf(EmitterScope::class)
        ->and($this->access)->toBeInstanceOf(ArrayAccess::class);
});

it('emit event', function () {
    $event = mock(DomainEvent::class);

    $this->hub->expects('trigger')
        ->withArgs(fn (object $e) => $e instanceof EventEmitted && $e->event === $event);

    $this->access->emit($event);
});

it('link event to stream', function (string $streamName) {
    $event = mock(DomainEvent::class);

    $this->hub->expects('trigger')
        ->withArgs(
            function (object $trigger) use ($event, $streamName) {
                return $trigger instanceof EventLinkedTo
                    && $trigger->event === $event
                    && $trigger->streamName === $streamName;
            }
        );

    $this->access->linkTo('stream-1', $event);
})->with('stream names');

it('stop projection', function () {
    $this->hub->expects('trigger')
        ->withArgs(fn (object $e) => $e instanceof ProjectionClosed);

    $this->access->stop();
});

it('get current processed stream name', function (string $streamName) {
    $this->hub->expects('expect')
        ->withArgs(fn (string $type) => $type === CurrentProcessedStream::class)
        ->andReturn($streamName);

    expect($this->access->streamName())->toBe($streamName);
})->with('stream names');

it('get clock', function () {
    expect($this->access->clock())->toBe($this->clock);
});
