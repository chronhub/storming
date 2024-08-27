<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Filter;

use Filter\ProjectionQueryFilter;
use InvalidArgumentException;
use Storm\Chronicler\Direction;
use Storm\Contract\Chronicler\InMemoryQueryFilter;
use Storm\Contract\Message\EventHeader;
use Storm\Projector\Stream\Filter\InMemoryLimitByOneQuery;
use Storm\Stream\StreamPosition;
use Storm\Tests\Stubs\Double\Message\SomeEvent;
use Storm\Tests\Stubs\StreamEventsGeneratorStub;

use function array_filter;
use function count;
use function iterator_to_array;

beforeEach(function () {
    $this->filter = new InMemoryLimitByOneQuery();
    $this->factory = new StreamEventsGeneratorStub();
});

test('default instance', function () {
    expect($this->filter)->toBeInstanceOf(InMemoryQueryFilter::class)
        ->and($this->filter)->toBeInstanceOf(ProjectionQueryFilter::class)
        ->and($this->filter->orderBy())->toBe(Direction::FORWARD);
});

test('filter stream event by internal position which match current stream position', function (int $position) {
    $streamEvents = iterator_to_array($this->factory->generateEventsWithInternalPosition(5));

    $streamPosition = new StreamPosition($position);
    $this->filter->setStreamPosition($streamPosition);

    $events = array_filter($streamEvents, $this->filter->apply());

    expect(count($events))->toBe(1)
        ->and($events[$position - 1])->toBeInstanceOf(SomeEvent::class)
        ->and($events[$position - 1]->header(EventHeader::INTERNAL_POSITION))->toBe($position);
})->with([
    ['at position 1' => 1],
    ['at position 2' => 2],
    ['at position 3' => 3],
    ['at position 4' => 4],
    ['at position 5' => 5],
]);

test('filter stream events by internal position which does not match current stream position', function (int $position) {
    $streamEvents = iterator_to_array($this->factory->generateEventsWithInternalPosition(5));
    $streamPosition = new StreamPosition($position);

    $this->filter->setStreamPosition($streamPosition);

    foreach ($streamEvents as $event) {
        expect($this->filter->apply()($event))->toBeFalse()
            ->and($event->header(EventHeader::INTERNAL_POSITION))->toBeLessThan($position);
    }
})->with([
    ['at position 6' => 6],
    ['at position 10' => 10],
    ['at position 100' => 100],
]);

test('raise exception when internal position is invalid', function () {
    $event = SomeEvent::fromContent([]);

    $this->filter->setStreamPosition(new StreamPosition(1));

    $this->filter->apply()($event);
})->throws(InvalidArgumentException::class, 'Invalid stream position:');

test('raise exception when internal position is missing', function (mixed $value) {
    $event = SomeEvent::fromContent([])->withHeader(EventHeader::INTERNAL_POSITION, $value);

    $this->filter->setStreamPosition(new StreamPosition(1));

    $this->filter->apply()($event);
})
    ->with([
        ['null position' => null],
        ['zero position' => 0],
        ['negative position' => -10],
        ['not integer position' => 0.2],
    ])
    ->throws(InvalidArgumentException::class, 'Invalid stream position:');
