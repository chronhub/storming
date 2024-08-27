<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Option;

use JsonSerializable;
use Options\ProjectionOption;
use Storm\Projector\Options\InMemoryOption;

test('default instance', function () {
    $options = new InMemoryOption();

    expect($options)->toBeInstanceOf(ProjectionOption::class)
        ->and($options)->toBeInstanceOf(JsonSerializable::class)
        ->and($options->getSignal())->toBeFalse()
        ->and($options->getCacheSize())->toBe(100)
        ->and($options->getBlockSize())->toBe(100)
        ->and($options->getSleep())->toBe([1000, 1, 10000])
        ->and($options->getTimeout())->toBe(1)
        ->and($options->getLockout())->toBe(0)
        ->and($options->getLoadLimiter())->toBe(100)
        ->and($options->getSleepEmitterOnFirstCommit())->toBe(0)
        ->and($options->getRetries())->toBeEmpty()
        ->and($options->getRecordGap())->toBeFalse()
        ->and($options->getDetectionWindows())->toBeNull();
});

test('json serialize default instance', function () {
    $options = new InMemoryOption();

    expect($options->jsonSerialize())
        ->toBe([
            'signal' => false,
            'cacheSize' => 100,
            'blockSize' => 100,
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
