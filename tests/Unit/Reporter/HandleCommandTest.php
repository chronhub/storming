<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Reporter;

use Storm\Contract\Message\Header;
use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Tracker\MessageStory;
use Storm\Message\Message;
use Storm\Reporter\Attribute\AsSubscriber;
use Storm\Reporter\Subscriber\HandleCommand;
use Storm\Tests\Stubs\Double\Message\SomeCommand;
use Storm\Tracker\TrackMessage;

beforeEach(function () {
    $this->tracker = new TrackMessage();
    $this->tracker->listen(new HandleCommand());
    $this->story = $this->tracker->newStory(Reporter::DISPATCH_EVENT);

    expect($this->story->handlers()->current())->toBeNull()
        ->and($this->story->isHandled())->toBeFalse();
});

afterEach(function () {
    $this->tracker = null;
    $this->story = null;
});

it('assert has subscriber attribute', function () {
    expect(HandleCommand::class)->toHaveAttribute(AsSubscriber::class, [[
        'eventName' => Reporter::DISPATCH_EVENT,
        'priority' => 0,
    ]]);
});

it('handle command and mark message handled when command handler as been set on story', function () {
    $called = false;
    $commandHandler = function () use (&$called) {
        $called = true;
    };

    $message = new Message(SomeCommand::fromContent(['foo' => 'bar']));

    $this->story->withMessage($message);
    $this->story->withHandlers([$commandHandler]);
    $this->tracker->disclose($this->story);

    expect($called)->toBeTrue()
        ->and($this->story->isHandled())->toBeTrue();
});

it('mark message handled when message event has been dispatched', function () {
    $message = new Message(SomeCommand::fromContent(['foo' => 'bar']));

    $this->story->withMessage($message);

    $this->tracker->onDispatch(function (MessageStory $story): void {
        $story->withMessage(
            $story->message()->withHeader(Header::EVENT_DISPATCHED, true)
        );
    }, priority: 10);

    $this->tracker->disclose($this->story);

    expect($this->story->isHandled())->toBeTrue();
});

it('does not mark message handled when message event has not been dispatched and no command handler set', function () {
    $message = new Message(SomeCommand::fromContent(['foo' => 'bar']));

    $this->story->withMessage($message);
    $this->tracker->disclose($this->story);

    expect($this->story->isHandled())->toBeFalse();
});
