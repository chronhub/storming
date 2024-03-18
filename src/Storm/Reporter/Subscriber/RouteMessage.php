<?php

declare(strict_types=1);

namespace Storm\Reporter\Subscriber;

use Closure;
use Storm\Contract\Tracker\MessageStory;
use Storm\Reporter\Routing;

/**
 * @deprecated
 */
final readonly class RouteMessage
{
    public function __construct(private Routing $routing)
    {
    }

    public function __invoke(): Closure
    {
        return function (MessageStory $story): void {
            $eventName = $story->message()->name();

            $messageHandlers = $this->routing->route($eventName);

            $story->withHandlers($messageHandlers);
        };
    }
}
