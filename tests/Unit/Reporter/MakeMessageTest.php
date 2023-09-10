<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Reporter;

use Storm\Contract\Message\MessageFactory;
use Storm\Contract\Reporter\Reporter;
use Storm\Message\Message;
use Storm\Reporter\Attribute\AsSubscriber;
use Storm\Reporter\Subscriber\MakeMessage;
use Storm\Tests\Stubs\Double\Message\SomeCommand;
use Storm\Tracker\TrackMessage;

beforeEach(function () {
    $this->tracker = new TrackMessage();
    $this->story = $this->tracker->newStory(Reporter::DISPATCH_EVENT);
});

afterEach(function () {
    $this->tracker = null;
    $this->story = null;
});

it('assert has subscriber attribute', function () {
    expect(MakeMessage::class)->toHaveAttribute(AsSubscriber::class, [[
        'eventName' => Reporter::DISPATCH_EVENT,
        'priority' => 100000,
    ]]);
});

it('create message from transient', function () {
    $event = SomeCommand::fromContent(['foo' => 'bar']);
    $message = new Message($event);

    $mock = mock(MessageFactory::class);
    $mock->shouldReceive('createMessageFrom')->with($event)->andReturn($message);
    $this->tracker->listen(new MakeMessage($mock));

    $this->story->withTransientMessage($event);
    $this->tracker->disclose($this->story);

    expect($this->story->message()->event())->toEqual($event);
});
