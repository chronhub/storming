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

it('create new instance', function (string $streamName): void {
    $provider = new InMemoryEventStream();

    expect($provider)->toHaveProperty('eventStreams')
        ->and($provider->hasRealStreamName($streamName))->toBe(false);
})->with('streamNames');

describe('create', function (): void {
    test('new event stream', function (string $streamName): void {
        $provider = new InMemoryEventStream();

        expect($provider->createStream($streamName, null))->toBe(true)
            ->and($provider->hasRealStreamName($streamName))->toBe(true);
    })->with('streamNames');

    test('does not duplicate when already exists', function (string $streamName): void {
        $provider = new InMemoryEventStream();

        expect($provider->createStream($streamName, null))->toBe(true)
            ->and($provider->hasRealStreamName($streamName))->toBe(true)
            ->and($provider->createStream($streamName, null))->toBe(false);
    })->with('streamNames');

    test('new event stream with category', function (): void {
        $provider = new InMemoryEventStream();

        expect($provider->createStream('credit_card', null, 'payment'))->toBe(true)
            ->and($provider->createStream('bitcoin', null, 'payment'))->toBe(true)
            ->and($provider->filterByAscendantCategories(['payment']))->toBe(['bitcoin', 'credit_card']);
    });

    test('does not duplicate category when already exists', function (): void {
        $provider = new InMemoryEventStream();

        expect($provider->createStream('bitcoin', null, 'payment'))->toBe(true)
            ->and($provider->createStream('bitcoin', null, 'payment'))->toBe(false)
            ->and($provider->filterByAscendantCategories(['payment']))->toBe(['bitcoin']);
    });
});

describe('delete', function (): void {
    test('event stream', function (string $streamName): void {
        $provider = new InMemoryEventStream();

        expect($provider->createStream($streamName, null))->toBe(true)
            ->and($provider->hasRealStreamName($streamName))->toBe(true)
            ->and($provider->deleteStream($streamName))->toBe(true)
            ->and($provider->hasRealStreamName($streamName))->toBe(false);
    })->with('streamNames');

    test('event stream per category', function (): void {
        $provider = new InMemoryEventStream();

        expect($provider->createStream('bitcoin', null, 'payment'))->toBe(true)
            ->and($provider->createStream('credit_card', null, 'payment'))->toBe(true)
            ->and($provider->filterByAscendantCategories(['payment']))->toBe(['bitcoin', 'credit_card'])
            ->and($provider->deleteStream('credit_card'))->toBe(true)
            ->and($provider->filterByAscendantCategories(['payment']))->toBe(['bitcoin']);
    });

    test('return false when event stream does not exists', function (string $streamName): void {
        $provider = new InMemoryEventStream();

        expect($provider->hasRealStreamName($streamName))->toBe(false)
            ->and($provider->deleteStream($streamName))->toBe(false);
    })->with('streamNames');
});

describe('filter event stream', function (): void {
    test('by stream names without internal streams prefixed with a dollar sign', function (): void {
        $provider = new InMemoryEventStream();

        expect($provider->createStream('foo', null))->toBe(true)
            ->and($provider->hasRealStreamName('foo'))->toBe(true)
            ->and($provider->createStream('$_internal', null))->toBe(true)
            ->and($provider->allWithoutInternal())->toBe(['foo']);
    });

    test('by stream names string or instance given by ascendant names', function (): void {
        $provider = new InMemoryEventStream();

        expect($provider->createStream('foo', null))->toBe(true)
            ->and($provider->createStream('bar', null, 'some_category'))->toBe(true)
            ->and($provider->createStream('$_internal', null))->toBe(true)
            ->and($provider->filterByAscendantStreams([new StreamName('foo'), '$_internal', 'some_category', 'bar', 'no_stream']))->toBe(['$_internal', 'foo']);
    });
});
