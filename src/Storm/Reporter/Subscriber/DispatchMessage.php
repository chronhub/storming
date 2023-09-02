<?php

declare(strict_types=1);

namespace Storm\Reporter\Subscriber;

use Storm\Contract\Message\MessageProducer;
use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Tracker\MessageStory;
use Storm\Reporter\Attribute\AsSubscriber;

#[AsSubscriber(eventName: Reporter::DISPATCH_EVENT, priority: 1000)]
final readonly class DispatchMessage
{
    public function __construct(private MessageProducer $messageProducer)
    {
    }

    public function __invoke(): callable
    {
        // todo assume sync
        return function (MessageStory $story): void {
            $message = $story->message();

            $dispatchedMessage = ($this->messageProducer)($message);

            $story->withMessage($dispatchedMessage);
        };
    }
}
