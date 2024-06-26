<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow;

use Closure;
use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\Subscriber;
use Storm\Projector\InteractWithProjection;
use Storm\Projector\Workflow\Notification\GetProjectionReport;
use Storm\Projector\Workflow\Notification\UserState\CurrentUserState;

beforeEach(function () {
    $this->context = $context = mock(ContextReader::class);
    $this->subscriber = $subscriber = mock(Subscriber::class);
    $this->projector = new class($context, $subscriber)
    {
        use InteractWithProjection;

        public function __construct(
            protected ContextReader $context,
            protected Subscriber $subscriber
        ) {}
    };
});

test('initialize context', function () {
    $userState = fn () => ['foo' => 'bar'];
    $this->context->shouldReceive('initialize')->with($userState)->once();
    $this->projector->initialize($userState);
});

test('subscribe to streams', function (array $streams) {
    $this->context->shouldReceive('subscribeToStream')->with(...$streams)->once();
    $this->projector->subscribeToStream(...$streams);
})->with([
    'with one stream' => [['stream-1']],
    'with many streams' => [['stream-1', 'stream-2']],
]);

test('subscribe to categories', function (array $categories) {
    $this->context->shouldReceive('subscribeToCategory')->with(...$categories)->once();
    $this->projector->subscribeToCategory(...$categories);
})->with([
    'with one category' => [['category-1']],
    'with many categories' => [['category-1', 'category-2']],
]);

test('subscribe to all', function () {
    $this->context->shouldReceive('subscribeToAll')->once();
    $this->projector->subscribeToAll();
});

test('set when', function () {
    $reactors = fn () => ['foo' => 'bar'];
    $this->context->shouldReceive('when')->with($reactors)->once();
    $this->projector->when($reactors);
});

test('set halt on', function () {
    $haltOn = fn () => ['foo' => 'bar'];
    $this->context->shouldReceive('haltOn')->with($haltOn)->once();
    $this->projector->haltOn($haltOn);
});

test('set describe', function () {
    $id = 'projection-id';
    $this->context->shouldReceive('withId')->with($id)->once();
    $this->projector->describe($id);
});

test('get state', function (array $state) {
    $hub = mock(NotificationHub::class);
    $hub->shouldReceive('expect')->with(CurrentUserState::class)->andReturn($state)->once();

    $callback = function (Closure $callback) use ($hub): true {
        $callback($hub);

        return true;
    };

    $this->subscriber->shouldReceive('interact')->withArgs($callback)->andReturn($state)->once();

    expect($this->projector->getState())->toBe($state);
})->with([
    'empty state' => [[]],
    'non-empty state' => [['foo' => 'bar']],
]);

test('get report', function (array $report) {
    $hub = mock(NotificationHub::class);
    $hub->shouldReceive('expect')->with(GetProjectionReport::class)->andReturn($report)->once();

    $callback = function (Closure $callback) use ($hub): true {
        $callback($hub);

        return true;
    };

    $this->subscriber->shouldReceive('interact')->withArgs($callback)->andReturn($report)->once();

    expect($this->projector->getReport())->toBe($report);

})->with([
    'empty report' => [[]],
    'non-empty report' => [['foo' => 'bar']],
]);
