<?php

declare(strict_types=1);

namespace Storm\Reporter\Subscriber;

use Closure;
use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Tracker\MessageStory;
use Storm\Reporter\Attribute\AsSubscriber;

//#[AsSubscriber(eventName: Reporter::DISPATCH_EVENT, priority: 1000)]
final readonly class RouteMessageHandlerAware
{
    /**
     * @param array<callable> $messageHandlers
     */
    public function __construct(private array $messageHandlers)
    {
    }

    public function __invoke(): Closure
    {
        return function (MessageStory $story): void {
            $story->withHandlers($this->messageHandlers);
        };
    }
}
