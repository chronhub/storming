<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Chronicler;

use Storm\Chronicler\InMemory\InMemoryEventStreamProvider;
use Storm\Stream\StreamName;

dataset('stream names', [
    'stream_1',
    'stream_2',
    'stream_3',
]);

beforeEach(function (): void {
    $this->eventStreamProvider = new InMemoryEventStreamProvider;
});

afterEach(function (): void {
    $this->eventStreamProvider = null;
});

it('create new instance', function (string $streamName): void {
    expect($this->eventStreamProvider)->toHaveProperty('eventStreams')
        ->and($this->eventStreamProvider->hasRealStreamName($streamName))->toBeFalse();
})->with('stream names');

describe('create', function (): void {
    test('new event stream', function (string $streamName): void {
        expect($this->eventStreamProvider->createStream($streamName, null))->toBeTrue()
            ->and($this->eventStreamProvider->hasRealStreamName($streamName))->toBeTrue();
    })->with('stream names');

    test('does not duplicate when already exists', function (string $streamName): void {
        expect($this->eventStreamProvider->createStream($streamName, null))->toBeTrue()
            ->and($this->eventStreamProvider->hasRealStreamName($streamName))->toBeTrue()
            ->and($this->eventStreamProvider->createStream($streamName, null))->toBeFalse();
    })->with('stream names');

    test('new event stream with category', function (): void {
        expect($this->eventStreamProvider->createStream('credit_card', null, 'payment'))->toBeTrue()
            ->and($this->eventStreamProvider->createStream('bitcoin', null, 'payment'))->toBeTrue()
            ->and($this->eventStreamProvider->filterByPartitions(['payment']))->toBe(['credit_card', 'bitcoin']);
    });

    test('does not duplicate category when already exists', function (): void {
        expect($this->eventStreamProvider->createStream('bitcoin', null, 'payment'))->toBeTrue()
            ->and($this->eventStreamProvider->createStream('bitcoin', null, 'payment'))->toBeFalse()
            ->and($this->eventStreamProvider->filterByPartitions(['payment']))->toBe(['bitcoin']);
    });
});

describe('delete', function (): void {
    test('event stream', function (string $streamName): void {
        expect($this->eventStreamProvider->createStream($streamName, null))->toBeTrue()
            ->and($this->eventStreamProvider->hasRealStreamName($streamName))->toBeTrue()
            ->and($this->eventStreamProvider->deleteStream($streamName))->toBeTrue()
            ->and($this->eventStreamProvider->hasRealStreamName($streamName))->toBeFalse();
    })->with('stream names');

    test('event stream per category', function (): void {
        expect($this->eventStreamProvider->createStream('bitcoin', null, 'payment'))->toBeTrue()
            ->and($this->eventStreamProvider->createStream('credit_card', null, 'payment'))->toBeTrue()
            ->and($this->eventStreamProvider->filterByPartitions(['payment']))->toBe(['bitcoin', 'credit_card'])
            ->and($this->eventStreamProvider->deleteStream('credit_card'))->toBeTrue()
            ->and($this->eventStreamProvider->filterByPartitions(['payment']))->toBe(['bitcoin']);
    });

    test('return false when event stream does not exists', function (string $streamName): void {
        expect($this->eventStreamProvider->hasRealStreamName($streamName))->toBeFalse()
            ->and($this->eventStreamProvider->deleteStream($streamName))->toBeFalse();
    })->with('stream names');
});

describe('filter event stream', function (): void {
    test('by stream names without internal streams prefixed with a dollar sign', function (): void {
        expect($this->eventStreamProvider->createStream('foo', null))->toBeTrue()
            ->and($this->eventStreamProvider->hasRealStreamName('foo'))->toBeTrue()
            ->and($this->eventStreamProvider->createStream('$_internal', null))->toBeTrue()
            ->and($this->eventStreamProvider->all())->toBe(['foo']);
    });

    test('by stream names string or instance given by ascendant names', function (): void {
        expect($this->eventStreamProvider->createStream('foo', null))->toBeTrue()
            ->and($this->eventStreamProvider->createStream('bar', null, 'some_category'))->toBeTrue()
            ->and($this->eventStreamProvider->createStream('$_internal', null))->toBeTrue()
            ->and($this->eventStreamProvider->filterByStreams([new StreamName('foo'), '$_internal', 'some_category', 'bar', 'no_stream']))->toBe(['foo', '$_internal']);
    });
});
