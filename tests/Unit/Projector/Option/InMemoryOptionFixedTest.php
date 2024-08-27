<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Option;

use JsonSerializable;
use Options\ProjectionOptionImmutable;
use ReflectionClass;
use Storm\Projector\Options\InMemoryFixedOption;

test('default instance', function () {
    $options = new InMemoryFixedOption();

    expect($options)->toBeInstanceOf(ProjectionOptionImmutable::class)
        ->and($options)->toBeInstanceOf(JsonSerializable::class)
        ->and($options->getSignal())->toBeFalse()
        ->and($options->getCacheSize())->toBe(100)
        ->and($options->getBlockSize())->toBe(1)
        ->and($options->getSleep())->toBe([1000, 1, 10000])
        ->and($options->getTimeout())->toBe(1)
        ->and($options->getLockout())->toBe(0)
        ->and($options->getLoadLimiter())->toBe(100)
        ->and($options->getSleepEmitterOnFirstCommit())->toBe(0)
        ->and($options->getRetries())->toBeEmpty()
        ->and($options->getRecordGap())->toBeFalse()
        ->and($options->getDetectionWindows())->toBeNull()
        ->and($options->getOnlyOnceDiscovery())->toBeFalse();
});

test('json serialize instance', function () {
    $options = new InMemoryFixedOption();

    expect($options->jsonSerialize())
        ->toBe([
            'signal' => false,
            'cacheSize' => 100,
            'blockSize' => 1,
            'timeout' => 1,
            'sleep' => [1000, 1, 10000],
            'lockout' => 0,
            'retries' => [],
            'recordGap' => false,
            'detectionWindows' => null,
            'loadLimiter' => 100,
            'onlyOnceDiscovery' => false,
            'sleepEmitterOnFirstCommit' => 0,
        ]);
});

test('instance has not constructor arguments', function () {
    $reflection = new ReflectionClass(InMemoryFixedOption::class);
    $parameters = $reflection->getConstructor()->getParameters();

    expect($parameters)->toBeEmpty();
});
