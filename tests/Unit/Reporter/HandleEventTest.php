<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Reporter;

use RuntimeException;
use Storm\Contract\Reporter\Reporter;
use Storm\Message\Message;
use Storm\Reporter\Subscriber\HandleEvent;
use Storm\Tests\Stubs\Double\Message\SomeEvent;
use Storm\Tracker\TrackMessage;
use Throwable;

beforeEach(function () {
    $this->tracker = new TrackMessage();
    $this->tracker->watch(new HandleEvent());
    $this->story = $this->tracker->newStory(Reporter::DISPATCH_EVENT);

    expect($this->story->handlers()->current())->toBeNull()
        ->and($this->story->isHandled())->toBeFalse();
});

afterEach(function () {
    $this->tracker = null;
    $this->story = null;
});

it('assert has subscriber attribute', function () {
    expect(HandleEvent::class)->toHaveSubscriberAttribute([[
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

    $command = SomeEvent::fromContent(['foo' => 'bar']);
    $message = new Message($command);

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

it('does not hold exception raised', function () {

    $called = 0;
    $exception = new RuntimeException('some exception');
    $eventHandler1 = function () use (&$called, $exception) {
        $called++;

        throw $exception;
    };
    $eventHandler2 = function () use (&$called) {
        $called++;
    };

    $message = new Message(SomeEvent::fromContent(['foo' => 'bar']));

    $this->story->withMessage($message);
    $this->story->withHandlers([$eventHandler1, $eventHandler2]);

    try {
        $this->tracker->disclose($this->story);
    } catch (Throwable $e) {
        expect($called)->toBe(1)
            ->and($this->story->isHandled())->toBeFalse()
            ->and($e)->toBe($exception);
    }
});
