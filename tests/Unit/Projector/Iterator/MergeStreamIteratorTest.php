<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Iterator;

use Countable;
use Generator;
use Iterator;
use Storm\Clock\PointInTime;
use Storm\Contract\Message\EventHeader;
use Storm\Contract\Message\Header;
use Storm\Projector\Iterator\MergeStreamIterator;
use Storm\Projector\Iterator\StreamIterator;
use Storm\Tests\Stubs\Double\Message\SomeEvent;

use function count;

beforeEach(function () {
    $this->clock = new PointInTime();
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
    $streams = getMergeStreams();

    $expectedOrder = [1, 1, 1, 2, 2, 2, 8, 8];
    $inOrder = [];

    foreach ($streams as $position => $event) {
        expect($position)->toBe($event->header(EventHeader::INTERNAL_POSITION))
            ->and($event)->toBeInstanceOf(SomeEvent::class);

        $inOrder[] = $position;
    }

    expect($inOrder)->toBe($expectedOrder);
});

test('iterate by prioritizing event time over all streams', function () {
    $streams = getMergeStreams();

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
    $streams = getMergeStreams();

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
    $streams = getMergeStreams();

    while ($streams->valid()) {
        $streams->next();
    }

    $streams->rewind();

    expect($streams->valid())->toBeFalse()
        ->and($streams->count())->toBe(0);
});

test('it fails prioritize stream event by time when it does not match clock format', function () {})->todo();

function getMergeStreams(): MergeStreamIterator
{
    $streams = collect(
        [
            [new StreamIterator(yieldStream1()), 'stream-1'],
            [new StreamIterator(yieldStream2()), 'stream-2'],
            [new StreamIterator(yieldStream3()), 'stream-3'],
        ]
    );

    $streams = new MergeStreamIterator(new PointInTime(), $streams);

    expect($streams->valid())->toBeTrue()
        ->and($streams->count())->toBe(8)
        ->and($streams->numberOfIterators)->toBe(3)
        ->and($streams->numberOfEvents)->toBe(8);

    return $streams;
}

function yieldStream1(): Generator
{
    $stream = 'stream-1';

    yield SomeEvent::fromContent(['stream' => $stream])->withHeaders(
        [
            EventHeader::INTERNAL_POSITION => 1,
            Header::EVENT_TIME => '2024-06-20T10:22:05.000003',
            'in_order' => 3,
        ]
    );

    yield SomeEvent::fromContent(['stream' => $stream])->withHeaders(
        [
            EventHeader::INTERNAL_POSITION => 2,
            Header::EVENT_TIME => '2024-06-20T10:22:05.000006',
            'in_order' => 6,
        ]
    );

    return 2;
}

function yieldStream2(): Generator
{
    $stream = 'stream-2';

    yield SomeEvent::fromContent(['stream' => $stream])->withHeaders(
        [
            EventHeader::INTERNAL_POSITION => 1,
            Header::EVENT_TIME => '2024-06-20T10:22:05.000001',
            'in_order' => 1,
        ]
    );

    yield SomeEvent::fromContent(['stream' => $stream])->withHeaders(
        [
            EventHeader::INTERNAL_POSITION => 2,
            Header::EVENT_TIME => '2024-06-20T10:22:05.000004',
            'in_order' => 4,
        ]
    );

    yield SomeEvent::fromContent(['stream' => $stream])->withHeaders(
        [
            EventHeader::INTERNAL_POSITION => 8,
            Header::EVENT_TIME => '2024-06-20T10:22:05.000008',
            'in_order' => 8,
        ]
    );

    return 3;
}

function yieldStream3(): Generator
{
    $stream = 'stream-3';

    yield SomeEvent::fromContent(['stream' => $stream])->withHeaders(
        [
            EventHeader::INTERNAL_POSITION => 1,
            Header::EVENT_TIME => '2024-06-20T10:22:05.000002',
            'in_order' => 2,
        ]
    );

    yield SomeEvent::fromContent(['stream' => $stream])->withHeaders(
        [
            EventHeader::INTERNAL_POSITION => 2,
            Header::EVENT_TIME => '2024-06-20T10:22:05.000005',
            'in_order' => 5,
        ]
    );

    yield SomeEvent::fromContent(['stream' => $stream])->withHeaders(
        [
            EventHeader::INTERNAL_POSITION => 8,
            Header::EVENT_TIME => '2024-06-20T10:22:05.000007',
            'in_order' => 7,
        ]
    );

    return 3;
}
