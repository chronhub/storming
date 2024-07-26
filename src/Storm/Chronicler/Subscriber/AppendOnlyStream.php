<?php

declare(strict_types=1);

namespace Storm\Chronicler\Subscriber;

use Closure;
use Storm\Chronicler\Attribute\Subscriber\AsStreamSubscriber;
use Storm\Chronicler\Exceptions\ConcurrencyException;
use Storm\Chronicler\Exceptions\StreamAlreadyExists;
use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\EventableChronicler;
use Storm\Contract\Tracker\StreamStory;

#[AsStreamSubscriber(
    event: EventableChronicler::APPEND_STREAM_EVENT,
    chronicler: 'chronicler.event.*'
)]
final class AppendOnlyStream
{
    public function __invoke(Chronicler $chronicler): Closure
    {
        return static function (StreamStory $story) use ($chronicler): void {
            try {
                $chronicler->append($story->promise());
            } catch (StreamAlreadyExists|StreamNotFound|ConcurrencyException $exception) {
                $story->withRaisedException($exception);
            }
        };
    }
}
