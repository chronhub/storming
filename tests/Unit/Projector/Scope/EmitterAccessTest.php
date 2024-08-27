<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Scope;

use Provider\Event\ProjectionClosed;
use Provider\Event\StreamEventEmitted;
use Provider\Event\StreamEventLinkedTo;
use Scope\EmitterScope;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Scope\EmitterAccess;
use Storm\Projector\Workflow\Notification\Promise\CurrentProcessedStream;

beforeEach(function () {
    $this->hub = mock(NotificationHub::class);
    $this->clock = mock(SystemClock::class);
    $this->access = new EmitterAccess($this->hub, $this->clock);
});

dataset('stream names', ['stream1', 'stream2', 'stream3']);

test('default instance', function () {
    expect($this->access)->toBeInstanceOf(EmitterScope::class);
});

test('emit event', function () {
    $event = mock(DomainEvent::class);

    $this->hub->expects('trigger')
        ->withArgs(fn (object $trigger) => $trigger instanceof StreamEventEmitted && $trigger->event === $event);

    $this->access->emit($event);
});

test('link event to stream', function (string $streamName) {
    $event = mock(DomainEvent::class);

    $this->hub->expects('trigger')
        ->withArgs(
            function (object $trigger) use ($event, $streamName) {
                return $trigger instanceof StreamEventLinkedTo
                    && $trigger->event === $event
                    && $trigger->streamName === $streamName;
            }
        );

    $this->access->linkTo($streamName, $event);
})->with('stream names');

test('stop projection', function () {
    $this->hub->expects('trigger')
        ->withArgs(fn (object $trigger) => $trigger instanceof ProjectionClosed);

    $this->access->stop();
});

test('get current processed stream name', function (string $streamName) {
    $this->hub->expects('await')
        ->withArgs(fn (string $notification) => $notification === CurrentProcessedStream::class)
        ->andReturn($streamName);

    expect($this->access->streamName())->toBe($streamName);
})->with('stream names');

test('get clock', function () {
    expect($this->access->clock())->toBe($this->clock);
});
