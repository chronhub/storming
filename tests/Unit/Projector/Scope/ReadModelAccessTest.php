<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Scope;

use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\ReadModel;
use Storm\Contract\Projector\ReadModelScope;
use Storm\Projector\Scope\ReadModelAccess;
use Storm\Projector\Workflow\Notification\Management\ProjectionClosed;
use Storm\Projector\Workflow\Notification\Stream\CurrentProcessedStream;

beforeEach(function () {
    $this->hub = mock(NotificationHub::class);
    $this->clock = mock(SystemClock::class);
    $this->readModel = mock(ReadModel::class);
    $this->access = new ReadModelAccess($this->hub, $this->readModel, $this->clock);
});

test('default instance', function () {
    expect($this->access)->toBeInstanceOf(ReadModelScope::class);
});

it('stop projection', function () {
    $this->hub->expects('trigger')
        ->withArgs(fn (object $e) => $e instanceof ProjectionClosed);

    $this->access->stop();
});

it('get read model', function () {
    expect($this->access->readModel())->toBe($this->readModel);
});

it('stack operation', function (string $operation, ...$arguments) {
    $this->readModel->expects('stack')->once()
        ->with($operation, ...$arguments);

    $this->access->stack($operation, ...$arguments);
})->with(
    [
        ['stack 1' => 'operation-1', 'arguments' => ['arg-1', 'arg-2']],
        ['stack 2' => 'operation-2', 'arguments' => ['arg-3', 'arg-4']],
        ['stack 2' => 'operation-3', 'arguments' => ['arg-5', 'arg-6']],
    ]
);

it('get current processed stream name', function (string $streamName) {
    $this->hub->expects('expect')
        ->withArgs(fn (string $notification) => $notification === CurrentProcessedStream::class)
        ->andReturn($streamName);

    expect($this->access->streamName())->toBe($streamName);
})->with(['stream names' => ['stream-1', 'stream-2', 'stream-3']]);

it('get clock', function () {
    expect($this->access->clock())->toBe($this->clock);
});
