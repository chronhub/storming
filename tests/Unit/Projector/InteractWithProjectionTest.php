<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow;

use Closure;
use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\InteractWithProjection;
use Storm\Projector\Provider\Manager;
use Storm\Projector\Workflow\Notification\Promise\CurrentUserState;
use Storm\Projector\Workflow\Notification\Promise\GetProjectionReport;

use function get_class;

beforeEach(function () {
    $this->context = $context = mock(ContextReader::class);
    $this->subscriber = $subscriber = mock(Manager::class);
    $this->projector = new class($context, $subscriber)
    {
        use InteractWithProjection;

        public function __construct(
            protected ContextReader $context,
            protected Manager $subscriber
        ) {}

        public function callDescribeIfNeeded(): void
        {
            $this->describeIfNeeded();
        }
    };
});

test('initialize context', function () {
    $userState = fn () => ['foo' => 'bar'];
    $this->context->expects('initialize')->with($userState);
    $this->projector->initialize($userState);
});

test('subscribe to streams', function (array $streams) {
    $this->context->expects('subscribeToStream')->with(...$streams);
    $this->projector->subscribeToStream(...$streams);
})->with([
    'with one stream' => [['stream1']],
    'with many streams' => [['stream1', 'stream2']],
]);

test('subscribe to categories', function (array $categories) {
    $this->context->expects('subscribeToPartition')->with(...$categories);
    $this->projector->subscribeToPartition(...$categories);
})->with([
    'with one category' => [['category1']],
    'with many categories' => [['category1', 'category2']],
]);

test('subscribe to all', function () {
    $this->context->expects('subscribeToAll');
    $this->projector->subscribeToAll();
});

test('set when', function () {
    $reactors = fn () => ['foo' => 'bar'];

    $this->context->expects('when')->with($reactors);
    $this->projector->when($reactors);
});

test('set halt on', function () {
    $haltOn = fn () => ['foo' => 'bar'];
    $this->context->expects('haltOn')->with($haltOn);
    $this->projector->haltOn($haltOn);
});

test('set describe', function () {
    $id = 'projection-id';
    $this->context->expects('withId')->with($id);

    $this->projector->describe($id);
});

test('set describe if id is not set', function () {
    $id = get_class($this->projector);

    $this->context->expects('id')->andReturn(null);
    $this->context->expects('withId')->with($id);

    $this->projector->callDescribeIfNeeded();
});

test('get state', function (array $state) {
    $hub = mock(NotificationHub::class);
    $hub->expects('await')->with(CurrentUserState::class)->andReturn($state);

    $callback = function (Closure $callback) use ($hub): true {
        $callback($hub);

        return true;
    };

    $this->subscriber->expects('interact')->withArgs($callback)->andReturn($state);

    expect($this->projector->getState())->toBe($state);
})->with([
    'empty state' => [[]],
    'non-empty state' => [['foo' => 'bar']],
]);

test('get report', function (array $report) {
    $hub = mock(NotificationHub::class);
    $hub->expects('await')->with(GetProjectionReport::class)->andReturn($report);

    $callback = function (Closure $callback) use ($hub): true {
        $callback($hub);

        return true;
    };

    $this->subscriber->expects('interact')->withArgs($callback)->andReturn($report);
    expect($this->projector->getReport())->toBe($report);
})->with([
    'empty report' => [[]],
    'non-empty report' => [['foo' => 'bar']],
]);
