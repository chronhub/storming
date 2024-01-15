<?php

declare(strict_types=1);

namespace Storm\Reporter\Subscriber;

use Closure;
use Storm\Contract\Message\MessageFactory;
use Storm\Contract\Tracker\MessageStory;

final readonly class MakeMessage
{
    public function __construct(private MessageFactory $messageFactory)
    {
    }

    public function __invoke(): Closure
    {
        return function (MessageStory $story): void {
            $message = $this->messageFactory->createMessageFrom($story->pullTransientMessage());

            $story->withMessage($message);
        };
    }
}
