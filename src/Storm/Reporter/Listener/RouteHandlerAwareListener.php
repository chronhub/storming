<?php

declare(strict_types=1);

namespace Storm\Reporter\Listener;

use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Tracker\Listener;
use Storm\Contract\Tracker\MessageStory;

final readonly class RouteHandlerAwareListener implements Listener
{
    /**
     * @param array<callable> $messageHandlers
     */
    public function __construct(private array $messageHandlers)
    {
    }

    public function name(): string
    {
        return Reporter::DISPATCH_EVENT;
    }

    public function priority(): int
    {
        return 0;
    }

    public function story(): callable
    {
        return function (MessageStory $story): void {
            $story->withHandlers($this->messageHandlers);
        };
    }

    public function origin(): string
    {
        return self::class;
    }
}
