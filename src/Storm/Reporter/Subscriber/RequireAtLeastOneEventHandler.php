<?php

declare(strict_types=1);

namespace Storm\Reporter\Subscriber;

use Closure;
use RuntimeException;
use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Tracker\MessageStory;
use Storm\Reporter\Attribute\AsSubscriber;

use function iterator_count;

#[AsSubscriber(eventName: Reporter::DISPATCH_EVENT, priority: 4900)]
final class RequireAtLeastOneEventHandler
{
    public function __invoke(): Closure
    {
        return function (MessageStory $story): void {
            $count = iterator_count($story->handlers());

            if ($count < 1) {
                throw new RuntimeException("Message {$story->message()->name()} must have at least one event handler");
            }
        };
    }
}
