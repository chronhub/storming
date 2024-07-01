<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Watcher;

use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Projector\Workflow\Watcher\EventStreamWatcher;

beforeEach(function () {
    $this->eventStreamProvider = mock(EventStreamProvider::class);
    $this->watcher = new EventStreamWatcher($this->eventStreamProvider);
});

function emptyEventStreams(): array
{
    return [];
}

function eventStreams(): array
{
    return ['foo', 'bar', 'baz'];
}

test('default instance', function () {
    expect($this->watcher->hasEventStreams())->toBeFalse()
        ->and($this->watcher->newEventStreams())->toBeEmpty();
});

test('discover empty event streams', function () {
    $this->watcher->discover(fn (EventStreamProvider $provider): array => emptyEventStreams());

    expect($this->watcher->hasEventStreams())->toBeFalse()
        ->and($this->watcher->newEventStreams())->toBeEmpty();
});

test('discover event streams', function () {
    $this->watcher->discover(fn (EventStreamProvider $provider): array => eventStreams());

    expect($this->watcher->hasEventStreams())->toBeTrue()
        ->and($this->watcher->newEventStreams())->toBe(eventStreams());
});

test('discover new event streams', function () {
    $this->watcher->discover(fn (EventStreamProvider $provider): array => eventStreams());

    expect($this->watcher->hasEventStreams())->toBeTrue()
        ->and($this->watcher->newEventStreams())->toBe(eventStreams());

    $this->watcher->discover(fn (EventStreamProvider $provider) => eventStreams());

    expect($this->watcher->hasEventStreams())->toBeTrue()
        ->and($this->watcher->newEventStreams())->toBeEmpty();
});

test('reset new event streams', function () {
    $this->watcher->discover(fn (EventStreamProvider $provider) => eventStreams());

    expect($this->watcher->hasEventStreams())->toBeTrue()
        ->and($this->watcher->newEventStreams())->toBe(eventStreams());

    $this->watcher->resetNewEventStreams();

    expect($this->watcher->hasEventStreams())->toBeTrue()
        ->and($this->watcher->newEventStreams())->toBeEmpty();
});
