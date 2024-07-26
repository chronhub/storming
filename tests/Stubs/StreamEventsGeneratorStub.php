<?php

declare(strict_types=1);

namespace Storm\Tests\Stubs;

use Generator;
use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\EventHeader;
use Storm\Stream\StreamName;
use Storm\Tests\Stubs\Double\Message\SomeEvent;

use function count;

final class StreamEventsGeneratorStub
{
    public function generateFromEmpty(): Generator
    {
        yield from [];

        return 0;
    }

    public function generateStreamNotFound(string $streamName): Generator
    {
        yield throw StreamNotFound::withStreamName(new StreamName($streamName));
    }

    /**
     * @return Generator<DomainEvent>
     */
    public function generateEvents(array $headers = [], array $content = [], int $count = 1): Generator
    {
        $num = 1;

        while ($num <= $count) {
            yield SomeEvent::fromContent($content)->withHeaders($headers);

            $num++;
        }

        return $count;
    }

    /**
     * Return SomeEvent domain events with empty content and empty headers.
     *
     * @return Generator<DomainEvent>
     */
    public function generateDummyEvents(int $count = 1): Generator
    {
        $num = 1;

        while ($num <= $count) {
            yield SomeEvent::fromContent([]);

            $num++;
        }

        return $count;
    }

    /**
     * @return Generator<DomainEvent>
     */
    public function generateEventsWithInternalPosition(int $count = 1): Generator
    {
        $num = 1;

        while ($num <= $count) {
            yield SomeEvent::fromContent([])->withHeader(EventHeader::INTERNAL_POSITION, $num);

            $num++;
        }

        return $num;
    }

    /**
     * @return Generator<DomainEvent>
     */
    public function generateGivenEvent(DomainEvent $event): Generator
    {
        yield $event;

        return 1;
    }

    /**
     * @param  array<DomainEvent>     $events
     * @return Generator<DomainEvent>
     */
    public function generateGivenEvents(array $events): Generator
    {
        foreach ($events as $event) {
            yield $event;
        }

        return count($events);
    }
}
