<?php

declare(strict_types=1);

namespace Storm\Chronicler\Subscriber;

use Closure;
use Storm\Chronicler\Attribute\Subscriber\AsStreamSubscriber;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\EventableChronicler;
use Storm\Contract\Tracker\StreamStory;

#[AsStreamSubscriber(
    event: EventableChronicler::FILTERED_STREAM_EVENT,
    chronicler: 'chronicler.event.*'
)]
final class FilterStreams
{
    public function __invoke(Chronicler $chronicler): Closure
    {
        return static function (StreamStory $story) use ($chronicler): void {
            $streamNames = $chronicler->filterStreams(...$story->promise());

            $story->deferred(static fn (): array => $streamNames);
        };
    }
}
