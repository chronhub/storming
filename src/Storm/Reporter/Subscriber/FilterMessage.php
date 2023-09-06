<?php

declare(strict_types=1);

namespace Storm\Reporter\Subscriber;

use RuntimeException;
use Storm\Contract\Reporter\MessageFilter;
use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Tracker\MessageStory;
use Storm\Reporter\Attribute\AsSubscriber;

#[AsSubscriber(eventName: Reporter::DISPATCH_EVENT, priority: 99000)]
final readonly class FilterMessage
{
    public function __construct(private MessageFilter $filter)
    {
    }

    public function __invoke(): callable
    {
        return function (MessageStory $story): void {
            if (! $this->filter->allows($story->message())) {
                // checkMe raise exception from filters and handle it here ?
                throw new RuntimeException('Dispatching message event is not allowed');
            }
        };
    }
}
