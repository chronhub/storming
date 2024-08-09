<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Option;

use Options\ProjectionOption;
use Options\ProjectionOptionImmutable;
use Storm\Projector\Options\DefaultOption;
use Storm\Projector\Options\InMemoryFixedOption;
use Storm\Projector\Options\InMemoryOption;
use Storm\Projector\Options\OptionResolver;

test('return constructed options if immutable', function () {
    $immutableOption = new InMemoryFixedOption();

    expect($immutableOption)->toBeInstanceOf(ProjectionOptionImmutable::class)
        ->and($immutableOption)->getSignal()->toBeFalse();

    $resolver = new OptionResolver($immutableOption);

    $options = $resolver(['signal' => true]);

    expect($options)->toBe($immutableOption)
        ->and($options->getSignal())->toBeFalse();
});

test('merge options with projection option constructed', function () {
    $mutableOption = new InMemoryOption();
    expect($mutableOption)->toBeInstanceOf(ProjectionOption::class)
        ->and($mutableOption)->not->toBeInstanceOf(ProjectionOptionImmutable::class)
        ->and($mutableOption)->getSignal()->toBeFalse();

    $resolver = new OptionResolver($mutableOption);

    $options = $resolver(['signal' => true]);

    expect($options)->not->toBe($mutableOption)
        ->and($options)->toBeInstanceOf(InMemoryOption::class)
        ->and($options->getSignal())->toBeTrue();
});

test('return default option instance with empty array constructed', function () {
    $resolver = new OptionResolver([]);

    $options = $resolver();

    expect($options)->toBeInstanceOf(DefaultOption::class);
});

test('return default option instance with empty array constructed and merge options', function () {
    expect(new InMemoryOption())->getSignal()->toBeFalse();

    $resolver = new OptionResolver([]);

    $options = $resolver(['signal' => true]);

    expect($options)->toBeInstanceOf(DefaultOption::class)
        ->and($options->getSignal())->toBeTrue();
});

test('assert live options is prioritized', function () {
    expect(new InMemoryOption())->getSignal()->toBeFalse();

    $resolver = new OptionResolver(['signal' => false]);

    $options = $resolver(['signal' => true]);

    expect($options)->toBeInstanceOf(DefaultOption::class)
        ->and($options->getSignal())->toBeTrue();
});
