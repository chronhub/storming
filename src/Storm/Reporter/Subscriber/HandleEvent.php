<?php

declare(strict_types=1);

namespace Storm\Reporter\Subscriber;

use Closure;
use Storm\Contract\Tracker\MessageStory;

final class HandleEvent
{
    public function __invoke(): Closure
    {
        return function (MessageStory $story): void {
            foreach ($story->handlers() as $eventHandler) {
                $eventHandler($story->message()->event());
            }

            $story->markHandled(true);
        };
    }
}
