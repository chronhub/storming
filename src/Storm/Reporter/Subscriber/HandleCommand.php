<?php

declare(strict_types=1);

namespace Storm\Reporter\Subscriber;

use Closure;
use Storm\Contract\Message\Header;
use Storm\Contract\Tracker\MessageStory;

final class HandleCommand
{
    public function __invoke(): Closure
    {
        return function (MessageStory $story): void {
            $messageHandler = $story->handlers()->current();

            if ($messageHandler) {
                $messageHandler($story->message()->event());
            }

            if ($messageHandler !== null || $story->message()->header(Header::EVENT_DISPATCHED) === true) {
                $story->markHandled(true);
            }
        };
    }
}
