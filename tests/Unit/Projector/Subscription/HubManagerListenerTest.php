<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Subscription;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Factory\Component\ComponentManager;
use Storm\Projector\Projection\HubManager;
use Storm\Tests\Stubs\CallableNotificationStub;
use Storm\Tests\Stubs\NonCallableNotificationStub;

use function get_class;

beforeEach(function () {
    $this->subscriptor = mock(ComponentManager::class);
    $this->hub = new HubManager($this->subscriptor);
});

dataset('listener handler', [
    'as string' => fn () => CallableNotificationStub::class,
    'as callable string' => fn () => fn () => CallableNotificationStub::class,
    'as callable object' => fn () => fn () => new CallableNotificationStub(5),
    'as array' => fn () => [CallableNotificationStub::class],
]);

test('default instance', function () {
    expect($this->hub)->toBeInstanceOf(NotificationHub::class)
        ->and($this->hub->hasEvent('foo'))->toBeFalse();
});

test('add listener with handler', function (string|callable|array $handler) {
    expect($this->hub->hasEvent('foo'))->toBeFalse();

    $this->hub->addEvent('foo', $handler);

    expect($this->hub->hasEvent('foo'))->toBeTrue();
})->with('listener handler');

test('add listeners with handlers', function () {
    $listener1 = new class {};
    $listener2 = new class {};

    $this->subscriptor->expects('capture')->andReturns($listener1);
    $this->subscriptor->expects('capture')->andReturns($listener2);

    $expected = [];
    $handler1 = function () use (&$expected) {
        $expected[] = 'foo';
    };
    $handler2 = function () use (&$expected) {
        $expected[] = 'bar';
    };

    $this->hub->addEvents([
        get_class($listener1) => $handler1,
        get_class($listener2) => $handler2,
    ]);

    $this->hub->emit($listener1);
    $this->hub->emit($listener2);

    expect($expected)->toBe(['foo', 'bar']);
});

test('merge handlers if listener already exists', function () {
    $listener = new class {};
    $this->subscriptor->expects('capture')->andReturns($listener);

    $expected = [];
    $handler1 = function () use (&$expected) {
        $expected[] = 'foo';
    };

    $handler2 = function () use (&$expected) {
        $expected[] = 'bar';
    };

    $this->hub->addEvent(get_class($listener), $handler1);
    $this->hub->addEvent(get_class($listener), $handler2);

    $this->hub->emit($listener);
});

test('forget listener', function () {
    $listener = new class {};
    $this->subscriptor->expects('capture')->andReturns($listener);

    $called = false;
    $this->hub->addEvent(get_class($listener), function () use (&$called) {
        $called = true;
    });

    $this->hub->emit($listener);

    expect($called)->toBeTrue();

    $this->hub->forgetEvent(get_class($listener));

    expect($this->hub->hasEvent(get_class($listener)))->toBeFalse();
});

test('notify many listeners', function () {
    $listener1 = new class {};
    $listener2 = new class {};

    $this->subscriptor->expects('capture')->andReturns($listener1);
    $this->subscriptor->expects('capture')->andReturns($listener2);

    $expected = [];
    $handler1 = function () use (&$expected) {
        $expected[] = 'foo';
    };
    $handler2 = function () use (&$expected) {
        $expected[] = 'bar';
    };

    $this->hub->addEvents([
        get_class($listener1) => $handler1,
        get_class($listener2) => $handler2,
    ]);

    $this->hub->emitMany($listener1, $listener2);

    expect($expected)->toBe(['foo', 'bar']);
});

test('notify when condition', function (bool $condition) {
    $listener1 = new class {};
    $listener2 = new class {};

    $condition
        ? $this->subscriptor->expects('capture')->andReturns($listener1)
        : $this->subscriptor->expects('capture')->andReturns($listener2);

    $expected = [];
    $handler1 = function () use (&$expected) {
        $expected[] = 'foo';
    };
    $handler2 = function () use (&$expected) {
        $expected[] = 'bar';
    };

    $this->hub->addEvents([
        get_class($listener1) => $handler1,
        get_class($listener2) => $handler2,
    ]);

    $this->hub->emitWhen(
        $condition,
        function (NotificationHub $hub) use ($listener1) {
            $hub->emit($listener1);
        },
        function (NotificationHub $hub) use ($listener2) {
            $hub->emit($listener2);
        }
    );

    $condition
        ? expect($expected)->toBe(['foo'])
        : expect($expected)->toBe(['bar']);
})->with([[true], [false]]);

test('expect return value from notification', function () {
    $listener = new class {};
    $this->subscriptor->expects('capture')->andReturns($listener);

    $expected = [];
    $handler = function (NotificationHub $hub, object $notification) use (&$expected) {
        $expected[] = $notification;
    };

    $this->hub->addEvent(get_class($listener), $handler);

    $this->hub->await($listener);

    expect($expected)->toBe([$listener]);
});

test('expect return value from notification with arguments but return null when not callable', function () {
    $listener = new NonCallableNotificationStub('foo', 5);
    $this->subscriptor->expects('capture')->andReturns($listener);

    $expected = [];
    $handler = function (NotificationHub $hub, object $notification, $resultOfCallable = null) use (&$expected) {
        $expected[] = [$notification, $resultOfCallable];
    };

    $this->hub->addEvent(get_class($listener), $handler);

    $this->hub->await($listener, 'foo', 5);

    expect($expected)->toBe([[$listener, null]]);
});

test('expect return value from notification with arguments as callable string', function () {
    $listener = CallableNotificationStub::class;
    $this->subscriptor->expects('capture')->andReturns(26); // result of callable

    $capture = null;
    $handler = function (NotificationHub $hub, object $notification, int $result) use (&$capture) {
        $capture = $result;
    };

    $this->hub->addEvent($listener, $handler);

    $result = $this->hub->await($listener, 26);

    expect($result)->toBe(26)
        ->and($capture)->toBe(26);
});

test('expect return value from callable string handler', function () {
    $listener = NonCallableNotificationStub::class;
    $this->subscriptor->expects('capture')->andReturns(42);

    $handler = CallableNotificationStub::class;
    $this->hub->addEvent($listener, $handler);

    $result = $this->hub->await($listener, 'foo', 5);

    expect($result)->toBe(42);
});
