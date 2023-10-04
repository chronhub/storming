<?php

declare(strict_types=1);

namespace Storm\Reporter\Subscriber;

use Closure;
use Storm\Attribute\Reference;
use Storm\Contract\Message\MessageProducer;
use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Tracker\MessageStory;
use Storm\Reporter\Attribute\AsSubscriber;

#[AsSubscriber(eventName: Reporter::DISPATCH_EVENT, priority: 1000)]
final readonly class DispatchMessage
{
    public function __construct(
        #[Reference('message.producer.sync')] private MessageProducer $messageProducer
    ) {
    }

    public function __invoke(): Closure
    {
        // todo assume sync
        return function (MessageStory $story): void {
            $message = $story->message();

            $dispatchedMessage = ($this->messageProducer)($message);

            $story->withMessage($dispatchedMessage);
        };
    }
}
