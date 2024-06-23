<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector;

use Closure;
use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\EmitterSubscriber;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\ProjectEmitter;
use Storm\Projector\Workflow\Notification\Management\ProjectionDiscarded;
use Storm\Projector\Workflow\Notification\Management\ProjectionRevised;

beforeEach(function () {
    $this->subscriber = mock(EmitterSubscriber::class);
    $this->context = mock(ContextReader::class);
    $this->streamName = 'stream_name';
    $this->projection = new ProjectEmitter($this->subscriber, $this->context, $this->streamName);
});

test('start projection', function (bool $runInBackground) {
    $this->context->shouldReceive('id')->andReturn('projection-id')->once();
    $this->subscriber->shouldReceive('start')->with($this->context, $runInBackground)->once();
    $this->projection->run($runInBackground);
})->with([
    'keep running' => [true],
    'run once' => [false],
]);

test('reset projection', function () {
    $hub = mock(NotificationHub::class);

    $hub->shouldReceive('trigger')->withArgs(
        fn (object $notification) => $notification instanceof ProjectionRevised
    )->once();

    $callback = function (Closure $callback) use ($hub) {
        $callback($hub);

        return true;
    };

    $this->subscriber->shouldReceive('interact')->once()->withArgs($callback);

    $this->projection->reset();
});

test('delete projection', function (bool $deleteEmittedEvents) {
    $hub = mock(NotificationHub::class);

    $trigger = function (object $notification) use ($deleteEmittedEvents) {
        return $notification instanceof ProjectionDiscarded
            && $notification->withEmittedEvents === $deleteEmittedEvents;
    };

    $hub->shouldReceive('trigger')->withArgs($trigger)->once();

    $callback = function (Closure $callback) use ($hub) {
        $callback($hub);

        return true;
    };

    $this->subscriber->shouldReceive('interact')->once()->withArgs($callback);

    $this->projection->delete($deleteEmittedEvents);

})->with([
    'delete emitted events' => [true],
    'keep emitted events' => [false],
]);

test('get stream name', function () {
    $streamName = $this->projection->getName();

    expect($streamName)->toBe($this->streamName);
});
