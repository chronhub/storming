<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Reporter;

use RuntimeException;
use Storm\Contract\Reporter\Reporter;
use Storm\Message\Message;
use Storm\Reporter\Attribute\AsSubscriber;
use Storm\Reporter\Exception\CollectedEventHandlerError;
use Storm\Reporter\Subscriber\TryHandleEvent;
use Storm\Tests\Stubs\Double\Message\SomeEvent;
use Storm\Tracker\TrackMessage;

beforeEach(function () {
    $this->tracker = new TrackMessage();
    $this->tracker->listen(new TryHandleEvent());
    $this->story = $this->tracker->newStory(Reporter::DISPATCH_EVENT);

    expect($this->story->handlers()->current())->toBeNull()
        ->and($this->story->isHandled())->toBeFalse();
});

afterEach(function () {
    $this->tracker = null;
    $this->story = null;
});

it('assert has subscriber attribute', function () {
    expect(TryHandleEvent::class)->toHaveAttribute(AsSubscriber::class, [[
        'eventName' => Reporter::DISPATCH_EVENT,
        'priority' => 0,
    ]]);
});

it('handle event and mark message handled when handlers have been set on story', function () {
    $called = 0;
    $eventHandler1 = function () use (&$called) {
        $called++;
    };
    $eventHandler2 = function () use (&$called) {
        $called++;
    };

    $message = new Message(SomeEvent::fromContent(['foo' => 'bar']));

    $this->story->withMessage($message);
    $this->story->withHandlers([$eventHandler1, $eventHandler2]);
    $this->tracker->disclose($this->story);

    expect($called)->toBe(2)
        ->and($this->story->isHandled())->toBeTrue();
});

it('mark message handled with no handler set in story', function () {
    $message = new Message(SomeEvent::fromContent(['foo' => 'bar']));

    $this->story->withMessage($message);
    $this->story->withHandlers([]);
    $this->tracker->disclose($this->story);

    expect($this->story->isHandled())->toBeTrue();
});

it('collect exception raised from handler', function () {
    $called = 0;
    $exception = new RuntimeException('some event exception');
    $eventHandler1 = function () use ($exception) {
        throw $exception;
    };
    $eventHandler2 = function () use (&$called) {
        $called++;
    };

    $message = new Message(SomeEvent::fromContent(['foo' => 'bar']));

    $this->story->withMessage($message);
    $this->story->withHandlers([$eventHandler1, $eventHandler2]);
    $this->tracker->disclose($this->story);

    expect($called)->toBe(1)
        ->and($this->story->hasException())->toBeTrue()
        ->and($this->story->exception())->toBeInstanceOf(CollectedEventHandlerError::class)
        ->and($this->story->exception()->getEventExceptions())->toBe([$exception])
        ->and($this->story->isHandled())->toBeTrue();
});

it('collect exceptions raised from handlers', function () {
    $called = 0;
    $exception1 = new RuntimeException('some event exception1');
    $exception2 = new RuntimeException('some event exception2');
    $eventHandler1 = function () use ($exception1, &$called) {
        $called++;

        throw $exception1;
    };
    $eventHandler2 = function () use ($exception2, &$called) {
        $called++;

        throw $exception2;
    };

    $message = new Message(SomeEvent::fromContent(['foo' => 'bar']));

    $this->story->withMessage($message);
    $this->story->withHandlers([$eventHandler1, $eventHandler2]);
    $this->tracker->disclose($this->story);

    expect($called)->toBe(2)
        ->and($this->story->hasException())->toBeTrue()
        ->and($this->story->exception())->toBeInstanceOf(CollectedEventHandlerError::class)
        ->and($this->story->exception()->getEventExceptions())->toBe([$exception1, $exception2])
        ->and($this->story->isHandled())->toBeTrue();
});
