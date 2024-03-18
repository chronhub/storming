<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Chronicler;

use Storm\Chronicler\InMemory\InMemoryEventStream;
use Storm\Stream\StreamName;

dataset('streamNames', [
    'stream_1',
    'stream_2',
    'stream_3',
]);

beforeEach(function (): void {
    $this->eventStreamProvider = new InMemoryEventStream();
});

afterEach(function (): void {
    $this->eventStreamProvider = null;
});

it('create new instance', function (string $streamName): void {
    expect($this->eventStreamProvider)->toHaveProperty('eventStreams')
        ->and($this->eventStreamProvider->hasRealStreamName($streamName))->toBe(false);
})->with('streamNames');

describe('create', function (): void {
    test('new event stream', function (string $streamName): void {
        expect($this->eventStreamProvider->createStream($streamName, null))->toBe(true)
            ->and($this->eventStreamProvider->hasRealStreamName($streamName))->toBe(true);
    })->with('streamNames');

    test('does not duplicate when already exists', function (string $streamName): void {
        expect($this->eventStreamProvider->createStream($streamName, null))->toBe(true)
            ->and($this->eventStreamProvider->hasRealStreamName($streamName))->toBe(true)
            ->and($this->eventStreamProvider->createStream($streamName, null))->toBe(false);
    })->with('streamNames');

    test('new event stream with category', function (): void {
        expect($this->eventStreamProvider->createStream('credit_card', null, 'payment'))->toBe(true)
            ->and($this->eventStreamProvider->createStream('bitcoin', null, 'payment'))->toBe(true)
            ->and($this->eventStreamProvider->filterByCategories(['payment']))->toBe(['bitcoin', 'credit_card']);
    });

    test('does not duplicate category when already exists', function (): void {
        expect($this->eventStreamProvider->createStream('bitcoin', null, 'payment'))->toBe(true)
            ->and($this->eventStreamProvider->createStream('bitcoin', null, 'payment'))->toBe(false)
            ->and($this->eventStreamProvider->filterByCategories(['payment']))->toBe(['bitcoin']);
    });
});

describe('delete', function (): void {
    test('event stream', function (string $streamName): void {
        expect($this->eventStreamProvider->createStream($streamName, null))->toBe(true)
            ->and($this->eventStreamProvider->hasRealStreamName($streamName))->toBe(true)
            ->and($this->eventStreamProvider->deleteStream($streamName))->toBe(true)
            ->and($this->eventStreamProvider->hasRealStreamName($streamName))->toBe(false);
    })->with('streamNames');

    test('event stream per category', function (): void {
        expect($this->eventStreamProvider->createStream('bitcoin', null, 'payment'))->toBe(true)
            ->and($this->eventStreamProvider->createStream('credit_card', null, 'payment'))->toBe(true)
            ->and($this->eventStreamProvider->filterByCategories(['payment']))->toBe(['bitcoin', 'credit_card'])
            ->and($this->eventStreamProvider->deleteStream('credit_card'))->toBe(true)
            ->and($this->eventStreamProvider->filterByCategories(['payment']))->toBe(['bitcoin']);
    });

    test('return false when event stream does not exists', function (string $streamName): void {
        expect($this->eventStreamProvider->hasRealStreamName($streamName))->toBe(false)
            ->and($this->eventStreamProvider->deleteStream($streamName))->toBe(false);
    })->with('streamNames');
});

describe('filter event stream', function (): void {
    test('by stream names without internal streams prefixed with a dollar sign', function (): void {
        expect($this->eventStreamProvider->createStream('foo', null))->toBe(true)
            ->and($this->eventStreamProvider->hasRealStreamName('foo'))->toBe(true)
            ->and($this->eventStreamProvider->createStream('$_internal', null))->toBe(true)
            ->and($this->eventStreamProvider->allWithoutInternal())->toBe(['foo']);
    });

    test('by stream names string or instance given by ascendant names', function (): void {
        expect($this->eventStreamProvider->createStream('foo', null))->toBe(true)
            ->and($this->eventStreamProvider->createStream('bar', null, 'some_category'))->toBe(true)
            ->and($this->eventStreamProvider->createStream('$_internal', null))->toBe(true)
            ->and($this->eventStreamProvider->filterByStreams([new StreamName('foo'), '$_internal', 'some_category', 'bar', 'no_stream']))->toBe(['$_internal', 'foo']);
    });
});
