<?php

declare(strict_types=1);

namespace Storm\Reporter\Listener;

use RuntimeException;
use Storm\Contract\Reporter\MessageFilter;
use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Tracker\Listener;
use Storm\Contract\Tracker\MessageStory;

final readonly class FilterMessageListener implements Listener
{
    public function __construct(private MessageFilter $messageFilter)
    {
    }

    public function name(): string
    {
        return Reporter::DISPATCH_EVENT;
    }

    public function priority(): int
    {
        return 99000;
    }

    public function story(): callable
    {
        return function (MessageStory $story): void {
            if (! $this->messageFilter->allows($story->message())) {
                throw new RuntimeException('Dispatching message event is not allowed');
            }
        };
    }

    public function origin(): string
    {
        return self::class;
    }
}
