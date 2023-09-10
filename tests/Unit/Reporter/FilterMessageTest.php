<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Reporter;

use RuntimeException;
use stdClass;
use Storm\Contract\Reporter\MessageFilter;
use Storm\Contract\Reporter\Reporter;
use Storm\Message\Message;
use Storm\Reporter\Attribute\AsSubscriber;
use Storm\Reporter\Subscriber\FilterMessage;
use Storm\Tests\Stubs\Double\Message\SomeCommand;
use Storm\Tests\Stubs\Double\Message\SomeEvent;
use Storm\Tests\Stubs\Double\Message\SomeQuery;
use Storm\Tracker\TrackMessage;

beforeEach(function () {
    $this->tracker = new TrackMessage();
    $this->mock = mock(MessageFilter::class);
    $this->tracker->listen(new FilterMessage($this->mock));
    $this->story = $this->tracker->newStory(Reporter::DISPATCH_EVENT);

    expect($this->tracker->listeners())->toHaveCount(1);
});

afterEach(function () {
    $this->tracker = null;
    $this->story = null;
    $this->mock = null;
});

dataset('messages', [
    new Message(new stdClass()),
    new Message(SomeCommand::fromContent(['foo' => 'bar'])),
    new Message(SomeEvent::fromContent(['foo' => 'bar'])),
    new Message(SomeQuery::fromContent(['foo' => 'bar'])),
]);

it('assert has subscriber attribute', function () {
    expect(FilterMessage::class)->toHaveAttribute(AsSubscriber::class, [[
        'eventName' => Reporter::DISPATCH_EVENT,
        'priority' => 99000,
    ]]);
});

it('allows dispatch message', function (Message $message) {
    $this->mock->shouldReceive('allows')->once()->with($message)->andReturn(true);

    $this->story->withMessage($message);

    $this->tracker->disclose($this->story);
})->with('messages');

it('deny dispatch message', function (Message $message) {
    $this->mock->shouldReceive('allows')->once()->with($message)->andReturn(false);

    $this->story->withMessage($message);

    $this->tracker->disclose($this->story);
})->throws(RuntimeException::class, 'Dispatching message event is not allowed')
    ->with('messages');
