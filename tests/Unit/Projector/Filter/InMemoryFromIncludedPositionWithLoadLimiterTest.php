<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Filter;

use Storm\Chronicler\Direction;
use Storm\Contract\Message\EventHeader;
use Storm\Projector\Stream\Filter\InMemoryFromToPosition;
use Storm\Projector\Stream\Filter\LoadLimiter;
use Storm\Stream\StreamPosition;
use Storm\Tests\Stubs\StreamEventsGeneratorStub;

use function array_values;
use function range;

beforeEach(function () {
    $this->eventStub = new StreamEventsGeneratorStub();
    $this->queryFilter = new InMemoryFromToPosition();
});

test('filter stream event with load limiter as max position and stream position as min position',
    function (int $maxPosition, int $minPosition, array $expected) {
        $loadLimiter = new LoadLimiter($maxPosition);
        $streamPosition = new StreamPosition($minPosition);
        $this->queryFilter->setLoadLimiter($loadLimiter);
        $this->queryFilter->setStreamPosition($streamPosition);

        $streamEvents = $this->eventStub->generateEventsWithInternalPosition(20);

        $internalPositions = [];

        foreach ($streamEvents as $event) {
            if ($this->queryFilter->apply()($event)) {
                $internalPositions[] = $event->header(EventHeader::INTERNAL_POSITION);
            }
        }
        expect($internalPositions)->toBe(array_values($expected))
            ->and($this->queryFilter->orderBy())->toBe(Direction::FORWARD);
    })
    ->with(
        [
            [10, 1, range(1, 10)],
            [15, 5, range(5, 15)],
            [30, 19, [19, 20]],
            [50, 20, [20]],
        ],
    );
