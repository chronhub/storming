<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector;

use Closure;
use Filter\ProjectionQueryFilter;
use Provider\Event\ProjectionDiscarded;
use Provider\Event\ProjectionRevised;
use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\ReadModelSubscriber;
use Storm\Projector\ProjectReadModel;

beforeEach(function () {
    $this->subscriber = mock(ReadModelSubscriber::class);
    $this->context = mock(ContextReader::class);
    $this->streamName = 'stream1';
    $this->projection = new ProjectReadModel($this->subscriber, $this->context, $this->streamName);
});

test('start projection', function (bool $runInBackground) {
    $this->context->expects('id')->andReturn('projection-id');
    $this->subscriber->expects('start')->with($this->context, $runInBackground);

    $this->projection->run($runInBackground);
})->with('keep projection running');

test('set projection query filter', function () {
    $queryFilter = mock(ProjectionQueryFilter::class);
    $this->context->expects('withQueryFilter')->with($queryFilter);

    $return = $this->projection->filter($queryFilter);

    expect($return)->toBe($this->projection);
});

test('get stream name', function () {
    $streamName = $this->projection->getName();

    expect($streamName)->toBe($this->streamName);
});

test('reset projection', function () {
    $hub = mock(NotificationHub::class);

    $hub->expects('trigger')->withArgs(fn (ProjectionRevised $trigger) => true);

    $callback = function (Closure $callback) use ($hub) {
        $callback($hub);

        return true;
    };

    $this->subscriber->expects('interact')->withArgs($callback);

    $this->projection->reset();
});

test('delete projection', function (bool $withEmittedEvents) {
    $hub = mock(NotificationHub::class);

    $trigger = fn (ProjectionDiscarded $trigger) => $trigger->withEmittedEvents === $withEmittedEvents;
    $hub->expects('trigger')->withArgs($trigger);

    $callback = function (Closure $callback) use ($hub) {
        $callback($hub);

        return true;
    };

    $this->subscriber->expects('interact')->withArgs($callback);

    $this->projection->delete($withEmittedEvents);
})->with('delete projection with emitted events');
