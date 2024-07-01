<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector;

use Closure;
use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\ProjectionQueryFilter;
use Storm\Contract\Projector\ReadModelSubscriber;
use Storm\Projector\ProjectReadModel;
use Storm\Projector\Workflow\Notification\Management\ProjectionDiscarded;
use Storm\Projector\Workflow\Notification\Management\ProjectionRevised;

beforeEach(function () {
    $this->subscriber = mock(ReadModelSubscriber::class);
    $this->context = mock(ContextReader::class);
    $this->streamName = 'streamName';
    $this->projection = new ProjectReadModel($this->subscriber, $this->context, $this->streamName);
});

test('start projection', function (bool $runInBackground) {
    $this->context->shouldReceive('id')->andReturn('projection-id')->once();
    $this->subscriber->shouldReceive('start')->with($this->context, $runInBackground)->once();
    $this->projection->run($runInBackground);
})->with('keep projection running');

test('set projection query filter', function () {
    $queryFilter = mock(ProjectionQueryFilter::class);
    $this->context->shouldReceive('withQueryFilter')->with($queryFilter)->once();

    $return = $this->projection->filter($queryFilter);

    expect($return)->toBe($this->projection);
});

test('get stream name', function () {
    $streamName = $this->projection->getName();

    expect($streamName)->toBe($this->streamName);
});

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

test('delete projection', function (bool $withEmittedEvents) {
    $hub = mock(NotificationHub::class);

    $trigger = function (object $notification) use ($withEmittedEvents) {
        return $notification instanceof ProjectionDiscarded
            && $notification->withEmittedEvents === $withEmittedEvents;
    };

    $hub->shouldReceive('trigger')->withArgs($trigger)->once();

    $callback = function (Closure $callback) use ($hub) {
        $callback($hub);

        return true;
    };

    $this->subscriber->shouldReceive('interact')->once()->withArgs($callback);

    $this->projection->delete($withEmittedEvents);

})->with('delete projection with emitted events');
