<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Message;

use stdClass;
use Storm\Message\ReadHeaders;
use Storm\Message\WriteHeaders;

beforeEach(function () {
    $this->instance = new class()
    {
        use ReadHeaders;
        use WriteHeaders;
    };
});

test('default instance', function () {
    expect($this->instance->headers())->toBeEmpty()
        ->and($this->instance->has('foo'))->toBeFalse()
        ->and($this->instance->hasNot('foo'))->toBeTrue();
});

test('return new instance', function () {
    expect($this->instance->has('foo'))->toBeFalse();

    $newInstance = $this->instance->withHeaders(['foo' => 'bar']);
    expect($newInstance->has('foo'))->toBeTrue()
        ->and($newInstance->header('foo'))->toBe('bar');

    $newInstance2 = $this->instance->withHeaders(['foo' => 'bar']);
    expect($newInstance2->has('foo'))->toBeTrue()
        ->and($newInstance2->header('foo'))->toBe('bar')
        ->and($newInstance)->not->toBe($newInstance2)
        ->and($newInstance)->toEqual($newInstance2);
});

test('set get header', function (int|float|string|bool|array|object|null $value) {
    expect($this->instance->has('foo'))->toBeFalse();

    $newInstance = $this->instance->withHeader('field', $value);

    expect($newInstance->has('field'))->toBeTrue()
        ->and($newInstance->header('field'))->toBe($value);

})->with([
    ['null value' => null],
    ['string value' => 'bar'],
    ['boolean value' => true],
    ['integer value' => 12],
    ['float value' => 1.2],
    ['array value' => [1, 'bar']],
    ['object value' => new stdClass()],
]);

test('set get headers', function () {
    expect($this->instance->has('foo'))->toBeFalse();

    $newInstance = $this->instance->withHeaders(['foo' => 'bar']);
    expect($newInstance->has('foo'))->toBeTrue()
        ->and($newInstance->header('foo'))->toBe('bar')
        ->and($newInstance->headers())->toBe(['foo' => 'bar']);

    $newInstance2 = $this->instance->withHeaders(['foo' => 'baz']);
    expect($newInstance2->has('foo'))->toBeTrue()
        ->and($newInstance2->header('foo'))->toBe('baz')
        ->and($newInstance2->headers())->toBe(['foo' => 'baz']);
});
