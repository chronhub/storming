<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Watcher;

use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Projector\Factory\Component\EventStreamDiscovery;

beforeEach(function () {
    $this->eventStreamProvider = mock(EventStreamProvider::class);
    $this->watcher = new EventStreamDiscovery($this->eventStreamProvider);
});

test('default instance', function () {
    expect($this->watcher->hasEventStreams())->toBeFalse()
        ->and($this->watcher->newEventStreams())->toBeEmpty();
});

test('discover empty event streams', function () {
    $streams = $this->watcher->discover(fn (EventStreamProvider $provider): array => []);

    expect($this->watcher->hasEventStreams())->toBeFalse()
        ->and($this->watcher->newEventStreams())->toBeEmpty()
        ->and($streams)->toBeEmpty();
});

test('discover event streams', function () {
    expect($this->watcher->hasEventStreams())->toBeFalse()
        ->and($this->watcher->newEventStreams())->toBeEmpty();

    $streams = $this->watcher->discover(fn (EventStreamProvider $provider): array => ['foo', 'bar', 'baz']);

    expect($this->watcher->hasEventStreams())->toBeTrue()
        ->and($this->watcher->newEventStreams())->toBe(['foo', 'bar', 'baz'])
        ->and($streams)->toBe(['foo', 'bar', 'baz']);
});

test('discover new event streams', function () {
    expect($this->watcher->hasEventStreams())->toBeFalse()
        ->and($this->watcher->newEventStreams())->toBeEmpty();

    $streams = $this->watcher->discover(fn (EventStreamProvider $provider): array => ['foo']);

    expect($this->watcher->hasEventStreams())->toBeTrue()
        ->and($this->watcher->newEventStreams())->toBe(['foo'])
        ->and($streams)->toBe(['foo']);

    $streams = $this->watcher->discover(fn (EventStreamProvider $provider) => ['bar', 'baz']);

    expect($this->watcher->newEventStreams())->toBe(['bar', 'baz'])
        ->and($streams)->toBe(['bar', 'baz']);
});

test('reset new event streams', function () {
    expect($this->watcher->hasEventStreams())->toBeFalse()
        ->and($this->watcher->newEventStreams())->toBeEmpty();

    $streams = $this->watcher->discover(fn (EventStreamProvider $provider) => ['foo', 'bar', 'baz']);

    expect($this->watcher->hasEventStreams())->toBeTrue()
        ->and($this->watcher->newEventStreams())->toBe(['foo', 'bar', 'baz'])
        ->and($streams)->toBe(['foo', 'bar', 'baz']);

    $this->watcher->resetNewEventStreams();

    expect($this->watcher->hasEventStreams())->toBeTrue()
        ->and($this->watcher->newEventStreams())->toBeEmpty();
});
