<?php

declare(strict_types=1);

namespace Storm\Reporter\Subscriber;

use React\Promise\Deferred;
use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Tracker\MessageStory;
use Storm\Reporter\Attribute\AsSubscriber;
use Throwable;

#[AsSubscriber(eventName: Reporter::DISPATCH_EVENT, priority: 0)]
final class HandleQuery
{
    public function __invoke(): callable
    {
        return function (MessageStory $story): void {
            $queryHandler = $story->handlers()->current();

            if ($queryHandler !== null) {
                $deferred = new Deferred();

                try {
                    $queryHandler($story->message()->event(), $deferred);
                } catch (Throwable $exception) {
                    $deferred->reject($exception);
                } finally {
                    $story->withPromise($deferred->promise());

                    $story->markHandled(true);
                }
            }
        };
    }
}
