<?php

declare(strict_types=1);

namespace Storm\Reporter\Subscriber;

use Closure;
use Storm\Contract\Message\Header;
use Storm\Contract\Message\MessageProducer;
use Storm\Contract\Tracker\MessageStory;
use Storm\Reporter\Routing;

final readonly class AsyncRouteMessage
{
    public function __construct(
        private Routing $routing,
        private MessageProducer $messageProducer
    ) {
    }

    public function __invoke(): Closure
    {
        return function (MessageStory $story): void {
            $message = $story->message();

            if ($message->header(Header::EVENT_DISPATCHED) === true) {
                $eventName = $story->message()->name();

                $messageHandlers = $this->routing->route($eventName);

                $story->withHandlers($messageHandlers);
            } else {
                $dispatchedMessage = ($this->messageProducer)($message);

                $story->withMessage($dispatchedMessage);
            }
        };
    }
}
