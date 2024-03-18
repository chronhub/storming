<?php

declare(strict_types=1);

namespace Storm\Reporter\Subscriber;

use Closure;
use React\Promise\Deferred;
use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Tracker\MessageStory;
use Storm\Reporter\Attribute\Subscriber\AsReporterSubscriber;
use Throwable;

#[AsReporterSubscriber(
    supports: ['reporter.query.*'],
    event: Reporter::DISPATCH_EVENT,
    priority: 0,
    autowire: true,
)]
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
