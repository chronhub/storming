<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Iterator;

use Countable;
use Iterator;
use Storm\Clock\ClockFactory;
use Storm\Contract\Message\EventHeader;
use Storm\Contract\Message\Header;
use Storm\Projector\Stream\Iterator\MergeStreamIterator;
use Storm\Tests\Stubs\Double\Message\SomeEvent;
use Storm\Tests\Stubs\MergeStreamIteratorStub;

use function count;

beforeEach(function () {
    $this->clock = ClockFactory::create();
    $this->stub = new MergeStreamIteratorStub();
});

test('default instance', function () {
    $iterator = new MergeStreamIterator($this->clock, collect());

    expect($iterator)->toBeInstanceOf(Iterator::class)
        ->and($iterator)->toBeInstanceOf(Countable::class)
        ->and($iterator)->toHaveProperty('numberOfIterators', 0)
        ->and($iterator)->toHaveProperty('numberOfEvents', 0)
        ->and($iterator->valid())->toBeFalse()
        ->and(count($iterator))->toBe(0);
});

test('it use internal position as key and domain event as value', function () {
    $streams = $this->stub->getMergeStreams();

    $inOrder = [];
    foreach ($streams as $position => $event) {
        expect($position)->toBe($event->header(EventHeader::INTERNAL_POSITION))
            ->and($event)->toBeInstanceOf(SomeEvent::class);

        $inOrder[] = $position;
    }

    expect($inOrder)->toBe($this->stub->expectedOrder);
});

test('iterate by prioritizing event time over all streams', function () {
    $streams = $this->stub->getMergeStreams();

    // remove last digit to allow for "in_order" to be appended
    $partialTime = '2024-06-20T10:22:05.00000';
    $lastTime = null;

    foreach ($streams as $position => $event) {
        $eventTime = $event->header(Header::EVENT_TIME);

        if ($lastTime !== null) {
            expect($lastTime)->toBeLessThan($eventTime);
        }

        $lastTime = $eventTime;

        expect($eventTime)->toBe($partialTime.$event->header('in_order'))
            ->and($position)->toBe($event->header(EventHeader::INTERNAL_POSITION));
    }

    expect($streams->valid())->toBeFalse()
        ->and($streams->count())->toBe(0)
        ->and($streams->numberOfIterators)->toBe(3)
        ->and($streams->numberOfEvents)->toBe(8);
});

test('can rewind iterator till the iterator is valid', function () {
    $streams = $this->stub->getMergeStreams();

    $currentEvent = $streams->current();
    $streams->next();
    $streams->next();
    $streams->next();

    $nextEvent = $streams->current();
    expect($streams->valid())->toBeTrue()->and($currentEvent)->not->toBe($nextEvent);

    $streams->rewind();

    expect($streams->valid())->toBeTrue()
        ->and($streams->count())->toBe(8)
        ->and($streams->current())->toBe($currentEvent);
});

test('can not rewind iterator when the iterator is no longer valid', function () {
    $streams = $this->stub->getMergeStreams();

    while ($streams->valid()) {
        $streams->next();
    }

    $streams->rewind();

    expect($streams->valid())->toBeFalse()
        ->and($streams->count())->toBe(0);
});

test('it fails prioritize stream event by time when it does not match clock format', function () {})->todo();
