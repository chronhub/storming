<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests;

use ReflectionClass;
use Storm\Reporter\Attribute\AsSubscriber;

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

expect()->extend('toHaveSubscriberAttribute', function (array $parameters, int $repeated = 1) {
    $class = $this->value;

    expect(class_exists($class))->toBe(true, "Class $class not found")
        ->and($repeated)->toBe(count($parameters));

    $reflectionClass = new ReflectionClass($class);
    $arguments = collect($reflectionClass->getAttributes(AsSubscriber::class))
        ->map(fn ($r) => $r->getArguments())->toArray();

    expect($arguments)->toHaveCount($repeated)
        ->and($parameters)->toBe($arguments);
});
