<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Stream;

use ArrayIterator;
use ArrayObject;
use Countable;
use IteratorAggregate;
use Storm\Stream\StreamEvents;
use Storm\Tests\Stubs\StreamEventsGeneratorStub;

use function iterator_to_array;

beforeEach(function () {
    $this->stub = new StreamEventsGeneratorStub();
});

test('default instance', function () {
    $events = $this->stub->generateDummyEvents(2);

    $streamEvents = new StreamEvents($events);

    expect($streamEvents)->toBeInstanceOf(Countable::class)
        ->and($streamEvents)->toBeInstanceOf(IteratorAggregate::class)
        ->and($streamEvents->count())->toBe(2);
});

test('can be constructed with empty iterable values', function (iterable $events) {
    $streamEvents = new StreamEvents($events);

    expect($streamEvents->count())->toBe(0)
        ->and(iterator_to_array($streamEvents))->toBe([]);
})->with([
    ['array' => fn () => []],
    ['generator' => fn () => $this->stub->generateFromEmpty()],
    ['iterator' => fn () => new ArrayIterator([])],
]);

test('can be constructed with iterable values and transform iterator', function (iterable $events) {
    $streamEvents = new StreamEvents($events);

    expect($streamEvents->count())->toBe(3)
        ->and($streamEvents->getIterator())->not->toBe($events);
})->with([
    ['array' => fn () => iterator_to_array($this->stub->generateDummyEvents(3))],
    ['generator' => fn () => $this->stub->generateDummyEvents(3)],
    ['array iterator' => fn () => new ArrayIterator(iterator_to_array($this->stub->generateDummyEvents(3)))],
]);

test('can be constructed with iterator aggregate and assign same iterator', function () {
    $events = new ArrayObject(iterator_to_array($this->stub->generateDummyEvents(3)));
    $streamEvents = new StreamEvents($events);

    expect($streamEvents->count())->toBe(3)
        ->and($streamEvents->getIterator())->toBe($events);
});
