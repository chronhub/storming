<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Scope;

use Provider\Event\ProjectionClosed;
use Scope\ReadModelScope;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\ReadModel;
use Storm\Projector\Scope\ReadModelAccess;
use Storm\Projector\Workflow\Notification\Promise\CurrentProcessedStream;

beforeEach(function () {
    $this->hub = mock(NotificationHub::class);
    $this->clock = mock(SystemClock::class);
    $this->readModel = mock(ReadModel::class);
    $this->access = new ReadModelAccess($this->hub, $this->readModel, $this->clock);
});

test('default instance', function () {
    expect($this->access)->toBeInstanceOf(ReadModelScope::class);
});

test('stop projection', function () {
    $this->hub
        ->expects('trigger')
        ->withArgs(fn (ProjectionClosed $trigger) => true);

    $this->access->stop();
});

test('get read model', function () {
    expect($this->access->readModel())->toBe($this->readModel);
});

test('stack operation', function (string $operation, ...$arguments) {
    $this->readModel
        ->expects('stack')
        ->with($operation, ...$arguments);

    $this->access->stack($operation, ...$arguments);
})->with(
    [
        ['stack 1' => 'methodName1', 'arguments' => ['arg-1', 'arg-2']],
        ['stack 2' => 'methodName2', 'arguments' => ['arg-3', 'arg-4']],
        ['stack 2' => 'methodName3', 'arguments' => ['arg-5', 'arg-6']],
    ]
);

test('get current processed stream name', function (string $streamName) {
    $this->hub->expects('await')
        ->withArgs(fn (string $notification) => $notification === CurrentProcessedStream::class)
        ->andReturn($streamName);

    expect($this->access->streamName())->toBe($streamName);
})->with(['stream names' => ['stream1', 'stream2', 'stream3']]);

test('get clock', function () {
    expect($this->access->clock())->toBe($this->clock);
});
