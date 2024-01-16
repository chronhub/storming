<?php

declare(strict_types=1);

namespace Storm\Reporter\Subscriber;

use Closure;
use Storm\Contract\Message\MessageProducer;
use Storm\Contract\Tracker\MessageStory;

final readonly class DispatchMessage
{
    public function __construct(private MessageProducer $messageProducer)
    {
    }

    public function __invoke(): Closure
    {
        return function (MessageStory $story): void {
            $message = $story->message();

            $dispatchedMessage = ($this->messageProducer)($message);

            $story->withMessage($dispatchedMessage);
        };
    }
}
