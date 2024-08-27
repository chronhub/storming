<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Iterator;

use Countable;
use InvalidArgumentException;
use Iterator;
use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Contract\Message\EventHeader;
use Storm\Projector\Stream\Iterator\StreamIterator;
use Storm\Tests\Stubs\Double\Message\SomeEvent;
use Storm\Tests\Stubs\StreamEventsGeneratorStub;

beforeEach(function () {
    $this->stub = new StreamEventsGeneratorStub();
});

test('default instance', function () {
    $streamIterator = new StreamIterator(
        $this->stub->generateEventsWithInternalPosition()
    );

    expect($streamIterator)
        ->toBeInstanceOf(Iterator::class)
        ->and($streamIterator)->toBeInstanceOf(Countable::class);
});

test('empty stream iterator', function () {
    $streamIterator = new StreamIterator(
        $this->stub->generateFromEmpty()
    );

    expect($streamIterator->current())->toBeNull()
        ->and($streamIterator->key())->toBeNull()
        ->and($streamIterator->valid())->toBeFalse()
        ->and($streamIterator->count())->toBe(0);
});

test('move cursor to the first event on construct', function () {
    $streamIterator = new StreamIterator(
        $this->stub->generateEventsWithInternalPosition()
    );

    expect($streamIterator->current())->toBeInstanceOf(SomeEvent::class)
        ->and($streamIterator->key())->toBe(1)
        ->and($streamIterator->valid())->toBeTrue()
        ->and($streamIterator->current()->header(EventHeader::INTERNAL_POSITION))->toBe(1)
        ->and($streamIterator->count())->toBe(1);
});

test('move cursor to next event', function () {
    $streamIterator = new StreamIterator(
        $this->stub->generateEventsWithInternalPosition(3)
    );

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

test('rewind iterator', function () {
    $streamIterator = new StreamIterator(
        $this->stub->generateEventsWithInternalPosition(5)
    );

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

test('iterate over events', function () {
    $streamIterator = new StreamIterator(
        $this->stub->generateEventsWithInternalPosition(3)
    );

    foreach ($streamIterator as $position => $event) {
        expect($event)->toBeInstanceOf(SomeEvent::class)
            ->and($position)->toBe($event->header(EventHeader::INTERNAL_POSITION));
    }
});

test('does not hold stream not found exception while iterating', function () {
    new StreamIterator(
        $this->stub->generateStreamNotFound('stream1')
    );
})->throws(StreamNotFound::class, 'Stream stream1 not found');

test('raise exception when internal position is invalid', function (mixed $position) {
    $event = SomeEvent::fromContent([])->withHeader(EventHeader::INTERNAL_POSITION, $position);

    try {
        new StreamIterator($this->stub->generateGivenEvent($event));
    } catch (InvalidArgumentException $exception) {
        expect($exception->getMessage())->toContain('Invalid stream position:');
    }
})
    ->with(
        [
            'zero' => 0,
            'negative' => -1,
            'string' => 'string',
            'float' => 1.1,
            'array' => [[]],
            'null' => fn () => null,
            'false' => false,
            'true' => true,
            'object' => new class {},
        ]
    );

test('raise exception when internal position key is not found in event headers', function () {
    $event = SomeEvent::fromContent([]);

    new StreamIterator($this->stub->generateGivenEvent($event));
})->throws(InvalidArgumentException::class, 'Invalid stream position: must be an integer, current value type is: NULL');
