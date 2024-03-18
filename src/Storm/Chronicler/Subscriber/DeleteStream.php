<?php

declare(strict_types=1);

namespace Storm\Chronicler\Subscriber;

use Closure;
use Storm\Chronicler\Attribute\Subscriber\AsStreamSubscriber;
use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\EventableChronicler;
use Storm\Contract\Tracker\StreamStory;

#[AsStreamSubscriber(
    event: EventableChronicler::DELETE_STREAM_EVENT,
    chronicler: 'chronicler.event.*'
)]
final class DeleteStream
{
    public function __invoke(Chronicler $chronicler): Closure
    {
        return static function (StreamStory $story) use ($chronicler): void {
            try {
                $chronicler->delete($story->promise());
            } catch (StreamNotFound $exception) {
                $story->withRaisedException($exception);
            }
        };
    }
}
