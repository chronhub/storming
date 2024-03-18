<?php

declare(strict_types=1);

namespace Storm\Chronicler\Subscriber;

use Closure;
use InvalidArgumentException;
use Storm\Chronicler\Attribute\Subscriber\AsStreamSubscriber;
use Storm\Chronicler\Direction;
use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\EventableChronicler;
use Storm\Contract\Tracker\StreamStory;
use Storm\Stream\Stream;

#[AsStreamSubscriber(
    event: EventableChronicler::ALL_STREAM_EVENT,
    chronicler: 'chronicler.event.*'
)]
final class RetrieveAllStream
{
    public function __invoke(Chronicler $chronicler): Closure
    {
        return static function (StreamStory $story) use ($chronicler): void {
            try {
                [$streamName, $aggregateId, $direction] = $story->promise();

                if ($direction !== Direction::FORWARD) {
                    throw new InvalidArgumentException('Direction must be forward');
                }

                $streamEvents = $chronicler->retrieveAll($streamName, $aggregateId, $direction);

                $newStream = new Stream($streamName, $streamEvents);

                $story->deferred(static fn (): Stream => $newStream);
            } catch (StreamNotFound $exception) {
                $story->withRaisedException($exception);
            }
        };
    }
}
