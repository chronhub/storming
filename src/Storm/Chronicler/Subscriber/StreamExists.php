<?php

declare(strict_types=1);

namespace Storm\Chronicler\Subscriber;

use Closure;
use Storm\Chronicler\Attribute\Subscriber\AsStreamSubscriber;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\EventableChronicler;
use Storm\Contract\Tracker\StreamStory;

#[AsStreamSubscriber(
    event: EventableChronicler::HAS_STREAM_EVENT,
    chronicler: 'chronicler.event.*'
)]
final class StreamExists
{
    public function __invoke(Chronicler $chronicler): Closure
    {
        return static function (StreamStory $story) use ($chronicler): void {
            $streamExists = $chronicler->hasStream($story->promise());

            $story->deferred(static fn (): bool => $streamExists);
        };
    }
}
