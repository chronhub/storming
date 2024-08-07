<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow;

use InvalidArgumentException;
use JsonSerializable;
use Storm\Projector\Stream\InMemoryEmittedStreams;

test('default instance', function () {
    $instance = new InMemoryEmittedStreams(3);

    expect($instance)->toBeInstanceOf(InMemoryEmittedStreams::class)
        ->and($instance)->toBeInstanceOf(JsonSerializable::class)
        ->and($instance->cacheSize)->toBe(3)
        ->and($instance->has('stream1'))->toBeFalse()
        ->and($instance->jsonSerialize())->toBe([0 => null, 1 => null, 2 => null]);
});

test('raise exception when cache size is less than 1', function (int $cacheSize) {
    new InMemoryEmittedStreams($cacheSize);
})
    ->with(['invalid cache size' => [-10, -1, 0]])
    ->throws(InvalidArgumentException::class, 'Stream cache size must be greater than 0');

test('raise exception when stream name already exists on push', function () {
    $instance = new InMemoryEmittedStreams(3);
    expect($instance->has('stream1'))->toBeFalse();

    $instance->push('stream1');

    $instance->push('stream1');
})->throws(InvalidArgumentException::class, 'Stream stream1 is already in the cache');

test('push three streams to cache and hit limit of three', function () {
    $instance = new InMemoryEmittedStreams(3);

    $instance->push('stream1');

    expect($instance->has('stream1'))->toBeTrue()
        ->and($instance->jsonSerialize())->toBe([0 => 'stream1', 1 => null, 2 => null]);

    $instance->push('stream2');

    expect($instance->has('stream2'))->toBeTrue()
        ->and($instance->jsonSerialize())->toBe([0 => 'stream1', 1 => 'stream2', 2 => null]);

    $instance->push('stream3');
    expect($instance->has('stream3'))->toBeTrue()
        ->and($instance->jsonSerialize())->toBe([0 => 'stream1', 1 => 'stream2', 2 => 'stream3']);
});

test('push four streams and exceed limit of three', function () {
    $instance = new InMemoryEmittedStreams(3);

    $instance->push('stream1');
    $instance->push('stream2');
    $instance->push('stream3');
    $instance->push('stream4');

    expect($instance->has('stream1'))->toBeFalse()
        ->and($instance->has('stream2'))->toBeTrue()
        ->and($instance->has('stream3'))->toBeTrue()
        ->and($instance->has('stream4'))->toBeTrue()
        ->and($instance->jsonSerialize())->toBe([0 => 'stream4', 1 => 'stream2', 2 => 'stream3']);
});
