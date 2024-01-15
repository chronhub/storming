<?php

declare(strict_types=1);

namespace Storm\Reporter\Subscriber;

use Closure;
use React\Promise\Deferred;
use Storm\Contract\Tracker\MessageStory;
use Throwable;

final class HandleQuery
{
    public function __invoke(): Closure
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
