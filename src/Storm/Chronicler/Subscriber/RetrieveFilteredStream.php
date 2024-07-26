<?php

declare(strict_types=1);

namespace Storm\Chronicler\Subscriber;

use Closure;
use Storm\Chronicler\Attribute\Subscriber\AsStreamSubscriber;
use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\EventableChronicler;
use Storm\Contract\Tracker\StreamStory;
use Storm\Stream\Stream;

#[AsStreamSubscriber(
    event: EventableChronicler::FILTERED_STREAM_EVENT,
    chronicler: 'chronicler.event.*'
)]
final class RetrieveFilteredStream
{
    public function __invoke(Chronicler $chronicler): Closure
    {
        return static function (StreamStory $story) use ($chronicler): void {
            try {
                [$streamName, $queryFilter] = $story->promise();

                $streamEvents = $chronicler->retrieveFiltered($streamName, $queryFilter);

                $newStream = new Stream($streamName, $streamEvents);

                $story->deferred(static fn (): Stream => $newStream);
            } catch (StreamNotFound $exception) {
                $story->withRaisedException($exception);
            }
        };
    }
}
