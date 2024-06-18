<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Iterator;

use Countable;
use Generator;
use Iterator;
use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Contract\Message\EventHeader;
use Storm\Projector\Iterator\StreamIterator;
use Storm\Stream\StreamName;
use Storm\Tests\Stubs\Double\Message\SomeEvent;

function emptyEvents(): Generator
{
    yield from [];
}

function generateEvents(int $count = 1): Generator
{
    $num = 1;

    while ($num <= $count) {
        yield SomeEvent::fromContent([])->withHeader(EventHeader::INTERNAL_POSITION, $num);

        $num++;
    }
}

function generateStreamNotFound(): Generator
{
    yield throw StreamNotFound::withStreamName(new StreamName('stream-1'));
}

it('test instance', function () {
    $streamIterator = new StreamIterator(emptyEvents());

    expect($streamIterator)
        ->toBeInstanceOf(Iterator::class)
        ->and($streamIterator)->toBeInstanceOf(Countable::class);
});

it('test empty stream iterator', function () {
    $streamIterator = new StreamIterator(emptyEvents());

    expect($streamIterator->current())->toBeNull()
        ->and($streamIterator->key())->toBeNull()
        ->and($streamIterator->valid())->toBeFalse()
        ->and($streamIterator->count())->toBe(0);
});

it('move cursor to the first event on construct', function () {
    $streamIterator = new StreamIterator(generateEvents());

    expect($streamIterator->current())->toBeInstanceOf(SomeEvent::class)
        ->and($streamIterator->key())->toBe(1)
        ->and($streamIterator->valid())->toBeTrue()
        ->and($streamIterator->current()->header(EventHeader::INTERNAL_POSITION))->toBe(1)
        ->and($streamIterator->count())->toBe(1);
});

it('move cursor to next event', function () {
    $streamIterator = new StreamIterator(generateEvents(3));

    $count = 0;

    while ($streamIterator->valid()) {
        expect($streamIterator->current())->toBeInstanceOf(SomeEvent::class)
            ->and($streamIterator->key())->toBe($streamIterator->current()->header(EventHeader::INTERNAL_POSITION))
            ->and($streamIterator->valid())->toBeTrue();

        $streamIterator->next();

        $count++;
    }

    expect($count)->toBe(3)
        ->and($streamIterator->current())->toBeNull()
        ->and($streamIterator->key())->toBeNull()
        ->and($streamIterator->valid())->toBeFalse();
});

it('rewind cursor', function () {
    $streamIterator = new StreamIterator(generateEvents(5));

    $streamIterator->next();
    $streamIterator->next();
    $streamIterator->next();
    $streamIterator->next();

    expect($streamIterator->current())->toBeInstanceOf(SomeEvent::class)
        ->and($streamIterator->key())->toBe(5)
        ->and($streamIterator->valid())->toBeTrue();

    $streamIterator->rewind();

    expect($streamIterator->current())->toBeInstanceOf(SomeEvent::class)
        ->and($streamIterator->key())->toBe(1)
        ->and($streamIterator->valid())->toBeTrue();
});

it('iterate over events', function () {
    $streamIterator = new StreamIterator(generateEvents(3));

    foreach ($streamIterator as $position => $event) {
        expect($event)->toBeInstanceOf(SomeEvent::class)
            ->and($position)->toBe($event->header(EventHeader::INTERNAL_POSITION));
    }
});

it('does not hold stream not found exception while iterating', function () {
    new StreamIterator(generateStreamNotFound());
})->throws(StreamNotFound::class, 'Stream stream-1 not found');
