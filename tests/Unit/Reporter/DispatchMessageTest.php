<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Reporter;

use Storm\Contract\Message\DomainCommand;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\DomainQuery;
use Storm\Contract\Message\Header;
use Storm\Contract\Message\MessageProducer;
use Storm\Contract\Message\Messaging;
use Storm\Contract\Reporter\Reporter;
use Storm\Message\Message;
use Storm\Message\SyncMessageProducer;
use Storm\Reporter\Subscriber\DispatchMessage;
use Storm\Tests\Stubs\Double\Message\SomeCommand;
use Storm\Tests\Stubs\Double\Message\SomeEvent;
use Storm\Tests\Stubs\Double\Message\SomeQuery;
use Storm\Tracker\TrackMessage;

dataset('messaging', [
    'command' => fn (): DomainCommand => SomeCommand::fromContent(['foo' => 'bar']),
    'event' => fn (): DomainEvent => SomeEvent::fromContent(['foo' => 'bar']),
    'query' => fn (): DomainQuery => SomeQuery::fromContent(['foo' => 'bar']),
]);

it('assert has subscriber attribute', function () {
    expect(DispatchMessage::class)->toHaveSubscriberAttribute([[
        'eventName' => Reporter::DISPATCH_EVENT,
        'priority' => 1000,
    ]]);
});

describe('dispatch message', function (): void {
    test('with mock message producer', function (Messaging $messaging): void {
        $messageProducer = mock(MessageProducer::class);
        $message = new Message($messaging);
        $messageProducer
            ->shouldReceive('__invoke')
            ->with($message)
            ->andReturn(new Message($message->event(), ['dispatched' => true]));

        $tracker = new TrackMessage();
        $tracker->watch(new DispatchMessage($messageProducer));

        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);
        $story->withMessage($message);

        $tracker->disclose($story);

        $dispatchedMessage = $story->message();

        expect($dispatchedMessage->header('dispatched'))->toBeTrue();
    })->with('messaging');

    test('with sync message producer', function (Messaging $messaging): void {
        $message = new Message($messaging);
        $messageProducer = new SyncMessageProducer();

        $tracker = new TrackMessage();
        $tracker->watch(new DispatchMessage($messageProducer));

        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);
        $story->withMessage($message);

        $tracker->disclose($story);

        $dispatchedMessage = $story->message();

        expect($dispatchedMessage->header(Header::EVENT_DISPATCHED))->toBeTrue();
    })->with('messaging');
});
