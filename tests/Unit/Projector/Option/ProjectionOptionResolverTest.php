<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Option;

use Storm\Projector\Options\DefaultOption;
use Storm\Projector\Options\InMemoryOption;
use Storm\Projector\Options\InMemoryOptionFixed;
use Storm\Projector\Options\ProjectionOptionResolver;

test('return constructed options if immutable', function () {
    $immutableOption = new InMemoryOptionFixed();
    expect($immutableOption)->getSignal()->toBeFalse();

    $resolver = new ProjectionOptionResolver($immutableOption);

    $options = $resolver(['signal' => true]);

    expect($options)->toBe($immutableOption)
        ->and($options->getSignal())->toBeFalse();
});

test('merge options with projection option constructed', function () {
    $mutableOption = new InMemoryOption();
    expect($mutableOption)->getSignal()->toBeFalse();

    $resolver = new ProjectionOptionResolver($mutableOption);

    $options = $resolver(['signal' => true]);

    expect($options)->not->toBe($mutableOption)
        ->and($options)->toBeInstanceOf(InMemoryOption::class)
        ->and($options->getSignal())->toBeTrue();
});

test('return default option instance with empty array constructed', function () {
    $resolver = new ProjectionOptionResolver([]);

    $options = $resolver();

    expect($options)->toBeInstanceOf(DefaultOption::class);
});

test('return default option instance with empty array constructed and merge options', function () {
    expect(new InMemoryOption())->getSignal()->toBeFalse();

    $resolver = new ProjectionOptionResolver([]);

    $options = $resolver(['signal' => true]);

    expect($options)->toBeInstanceOf(DefaultOption::class)
        ->and($options->getSignal())->toBeTrue();
});

test('assert live options is prioritized', function () {
    expect(new InMemoryOption())->getSignal()->toBeFalse();

    $resolver = new ProjectionOptionResolver(['signal' => false]);

    $options = $resolver(['signal' => true]);

    expect($options)->toBeInstanceOf(DefaultOption::class)
        ->and($options->getSignal())->toBeTrue();
});
