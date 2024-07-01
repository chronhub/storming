<?php

declare(strict_types=1);

namespace Storm\Tests\Stubs;

use Generator;
use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Contract\Message\EventHeader;
use Storm\Stream\StreamName;
use Storm\Tests\Stubs\Double\Message\SomeEvent;

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

    public function generateEventsWithInternalPosition(int $count = 1): Generator
    {
        $num = 1;

        while ($num <= $count) {
            yield SomeEvent::fromContent([])->withHeader(EventHeader::INTERNAL_POSITION, $num);

            $num++;
        }

        return $num;
    }
}
