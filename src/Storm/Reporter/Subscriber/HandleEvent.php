<?php

declare(strict_types=1);

namespace Storm\Reporter\Subscriber;

use Closure;
use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Tracker\MessageStory;
use Storm\Reporter\Attribute\Subscriber\AsReporterSubscriber;

#[AsReporterSubscriber(
    supports: ['reporter.event.*'],
    event: Reporter::DISPATCH_EVENT,
    priority: 0,
    autowire: true,
)]
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
