<?php

declare(strict_types=1);

namespace Storm\Reporter\Subscriber;

use Closure;
use Storm\Contract\Message\Header;
use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Tracker\MessageStory;
use Storm\Reporter\Attribute\AsSubscriber;
use Storm\Reporter\Routing;

#[AsSubscriber(eventName: Reporter::DISPATCH_EVENT, priority: 1000)]
final readonly class RouteMessage
{
    public function __construct(private Routing $routing)
    {
    }

    public function __invoke(): Closure
    {
        return function (MessageStory $story): void {
            $message = $story->message();

            $messageName = $message->header(Header::EVENT_TYPE) ?? $message->event()::class;

            $messageHandlers = $this->routing->route($messageName);

            $story->withHandlers($messageHandlers);
        };
    }
}
