<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests;

expect()->extend('toHaveScalarProperty', function (string $property, null|int|float|string|array $expected) {
    $value = getPrivateProperty($this->value, $property);

    return expect($value)->toBe($expected);
});

expect()->extend('toHaveSameObjectProperty', function (string $property, object $expected) {
    $value = getPrivateProperty($this->value, $property);

    return expect($value)->toBe($expected);
});

expect()->extend('toHaveEqualObjectProperty', function (string $property, object $expected) {
    $value = getPrivateProperty($this->value, $property);

    return expect($value)->toEqual($expected);
});
