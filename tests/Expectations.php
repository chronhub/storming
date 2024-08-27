<?php

declare(strict_types=1);

namespace Storm\Tests;

use React\Promise\PromiseInterface;
use ReflectionClass;

use function class_exists;
use function count;

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

expect()->extend('toBePromiseResult', function (mixed $expected) {
    return expect($this->value)->toBeInstanceOf(PromiseInterface::class)
        ->and(getPromiseResult($this->value))->toBe($expected);
});

expect()->extend('toHaveAttribute', function (string $attribute, array $parameters, int $repeated = 1) {
    $class = $this->value;

    expect(class_exists($class))->toBe(true, "Class $class not found")
        ->and(class_exists($attribute))->toBe(true, "Attribute class $attribute not found")
        ->and($repeated)->toBe(count($parameters));

    $reflectionClass = new ReflectionClass($class);
    $arguments = collect($reflectionClass->getAttributes($attribute))
        ->map(fn ($r) => $r->getArguments())->toArray();

    expect($arguments)->toHaveCount($repeated)
        ->and($parameters)->toBe($arguments);
});
