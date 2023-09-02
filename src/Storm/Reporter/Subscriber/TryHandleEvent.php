<?php

declare(strict_types=1);

namespace Storm\Reporter\Subscriber;

use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Tracker\MessageStory;
use Storm\Reporter\Attribute\AsSubscriber;
use Storm\Reporter\Exception\CollectedEventHandlerError;
use Throwable;

#[AsSubscriber(eventName: Reporter::DISPATCH_EVENT, priority: 0)]
final class TryHandleEvent
{
    public function __invoke(): callable
    {
        return function (MessageStory $story): void {
            $exceptions = [];

            foreach ($story->handlers() as $messageHandler) {
                try {
                    $messageHandler($story->message()->event());
                } catch (Throwable $exception) {
                    $exceptions[] = $exception;
                }
            }

            $story->markHandled(true);

            if ($exceptions !== []) {
                $story->withRaisedException(
                    CollectedEventHandlerError::fromExceptions(...$exceptions)
                );
            }
        };
    }
}
