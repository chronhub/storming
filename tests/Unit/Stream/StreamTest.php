<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Stream;

use ArrayIterator;
use Countable;
use Generator;
use Illuminate\Support\Collection;
use Iterator;
use Storm\Stream\Stream;
use Storm\Stream\StreamName;
use Storm\Tests\Stubs\Double\Message\SomeEvent;

use function iterator_to_array;

test('create new stream instance with empty events', function (StreamName $streamName) {
    $stream = new Stream($streamName);

    expect($stream)->toBeInstanceOf(Countable::class)
        ->and($stream->name)->toBe($streamName)
        ->and($stream->name())->toBe($streamName)
        ->and(iterator_to_array($stream->events()))->toBeEmpty();
})->with('stream names');

test('create new stream instance with iterable stream events', function (iterable $events) {
    $stream = new Stream(new StreamName('stream_name'), $events);

    expect($stream->events())->toBeInstanceOf('Generator');

    $events = iterator_to_array($stream->events());

    expect($events)->toHaveCount(3)
        ->and($events[0])->toBeInstanceOf(SomeEvent::class);

})->with('iterable');

test('can be counted', function (iterable $events) {
    $stream = new Stream(new StreamName('stream_name'), $events);

    expect($stream)->toBeInstanceOf(Countable::class)
        ->and($stream->count())->toBe(3);

    $streamEvents = $stream->events();
    foreach ($streamEvents as $streamEvent) {
        expect($streamEvent)->toBeInstanceOf(SomeEvent::class);
    }

    expect($streamEvents->getReturn())->toBe(3);
})->with('iterable');

test('return number of events from generator', function (iterable $events) {
    $stream = new Stream(new StreamName('stream_name'), $events);

    $streamEvents = $stream->events();
    foreach ($streamEvents as $streamEvent) {
        expect($streamEvent)->toBeInstanceOf(SomeEvent::class);
    }

    expect($streamEvents->getReturn())->toBe(3);
})->with('iterable');

dataset('stream names', [
    new StreamName('stream1'),
    new StreamName('stream2'),
    new StreamName('stream3'),
]);

dataset('iterable',
    [
        fn (): array => [
            SomeEvent::fromContent(['event' => 'event1']),
            SomeEvent::fromContent(['event' => 'event2']),
            SomeEvent::fromContent(['event' => 'event3']),
        ],

        fn (): Generator => yield from [
            SomeEvent::fromContent(['event' => 'event1']),
            SomeEvent::fromContent(['event' => 'event2']),
            SomeEvent::fromContent(['event' => 'event3']),
        ],

        fn (): Iterator => new ArrayIterator([
            SomeEvent::fromContent(['event' => 'event1']),
            SomeEvent::fromContent(['event' => 'event2']),
            SomeEvent::fromContent(['event' => 'event3']),
        ]),

        fn (): Collection => new Collection([
            SomeEvent::fromContent(['event' => 'event1']),
            SomeEvent::fromContent(['event' => 'event2']),
            SomeEvent::fromContent(['event' => 'event3']),
        ]),
    ]
);
