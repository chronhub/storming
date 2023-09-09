<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Reporter;

use Storm\Contract\Message\Header;
use Storm\Contract\Reporter\Reporter;
use Storm\Message\Message;
use Storm\Reporter\Attribute\AsSubscriber;
use Storm\Reporter\Subscriber\NameReporter;
use Storm\Tests\Stubs\Double\Message\SomeCommand;
use Storm\Tests\Stubs\Double\Message\SomeEvent;
use Storm\Tests\Stubs\Double\Message\SomeQuery;
use Storm\Tracker\TrackMessage;

beforeEach(function () {
    $this->tracker = new TrackMessage();
    $this->story = $this->tracker->newStory(Reporter::DISPATCH_EVENT);
});

afterEach(function () {
    $this->tracker = null;
    $this->story = null;
});

dataset('messages', [
    new Message(SomeCommand::fromContent(['foo' => 'bar'])),
    new Message(SomeEvent::fromContent(['foo' => 'bar'])),
    new Message(SomeQuery::fromContent(['foo' => 'bar'])),
]);

it('assert has subscriber attribute', function () {
    expect(NameReporter::class)->toHaveAttribute(AsSubscriber::class, [[
        'eventName' => Reporter::DISPATCH_EVENT,
        'priority' => 98000,
    ]]);
});

it('add reporter name to message header', function (Message $message) {
    $this->tracker->watch(new NameReporter('foo'));
    expect($this->tracker->listeners())->toHaveCount(1);

    $this->story->withMessage($message);
    $this->tracker->disclose($this->story);

    $newMessage = $this->story->message();

    expect($newMessage->has(Header::REPORTER_ID))->toBeTrue()
        ->and($newMessage->header(Header::REPORTER_ID))->toBe('foo');
})->with('messages');

it('not add reporter name to message header', function (Message $message) {
    $subscriber = new NameReporter('foo');
    $this->tracker->watch($subscriber);

    $message = $message->withHeader(Header::REPORTER_ID, 'bar');

    $this->story->withMessage($message);
    $this->tracker->disclose($this->story);

    $sameMessage = $this->story->message();

    expect($sameMessage)->toBe($message);
})->with('messages');
