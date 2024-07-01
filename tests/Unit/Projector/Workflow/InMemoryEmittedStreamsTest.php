<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow;

use InvalidArgumentException;
use JsonSerializable;
use Storm\Projector\Workflow\InMemoryEmittedStreams;

test('default instance', function () {
    $instance = new InMemoryEmittedStreams(3);

    expect($instance)->toBeInstanceOf(InMemoryEmittedStreams::class)
        ->and($instance)->toBeInstanceOf(JsonSerializable::class)
        ->and($instance->cacheSize)->toBe(3)
        ->and($instance->has('stream-1'))->toBeFalse()
        ->and($instance->jsonSerialize())->toBe([0 => null, 1 => null, 2 => null]);
});

test('raise exception when cache size is less than 1', function (int $cacheSize) {
    new InMemoryEmittedStreams($cacheSize);
})
    ->with(['invalid cache size' => [-10, -1, 0]])
    ->throws(InvalidArgumentException::class, 'Stream cache size must be greater than 0');

test('push three streams to cache and hit limit of three', function () {
    $instance = new InMemoryEmittedStreams(3);

    $instance->push('stream-1');

    expect($instance->has('stream-1'))->toBeTrue()
        ->and($instance->jsonSerialize())->toBe([0 => 'stream-1', 1 => null, 2 => null]);

    $instance->push('stream-2');

    expect($instance->has('stream-2'))->toBeTrue()
        ->and($instance->jsonSerialize())->toBe([0 => 'stream-1', 1 => 'stream-2', 2 => null]);

    $instance->push('stream-3');

    expect($instance->has('stream-3'))->toBeTrue()
        ->and($instance->jsonSerialize())->toBe([0 => 'stream-1', 1 => 'stream-2', 2 => 'stream-3']);
});

test('push four streams and exceed limit of three', function () {
    $instance = new InMemoryEmittedStreams(3);

    $instance->push('stream-1');
    $instance->push('stream-2');
    $instance->push('stream-3');
    $instance->push('stream-4');

    expect($instance->has('stream-1'))->toBeFalse()
        ->and($instance->has('stream-2'))->toBeTrue()
        ->and($instance->has('stream-3'))->toBeTrue()
        ->and($instance->has('stream-4'))->toBeTrue()
        ->and($instance->jsonSerialize())->toBe([0 => 'stream-4', 1 => 'stream-2', 2 => 'stream-3']);
});
