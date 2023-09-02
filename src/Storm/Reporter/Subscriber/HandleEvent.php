<?php

declare(strict_types=1);

namespace Storm\Reporter\Subscriber;

use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Tracker\MessageStory;
use Storm\Reporter\Attribute\AsSubscriber;

#[AsSubscriber(eventName: Reporter::DISPATCH_EVENT, priority: 0)]
final class HandleEvent
{
    public function __invoke(): callable
    {
        return function (MessageStory $story): void {
            foreach ($story->handlers() as $eventHandler) {
                $eventHandler($story->message()->event());
            }

            $story->markHandled(true);
        };
    }
}
