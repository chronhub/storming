<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Option;

use JsonSerializable;
use Storm\Contract\Projector\ProjectionOption;
use Storm\Projector\Options\InMemoryOption;

test('default instance', function () {
    $options = new InMemoryOption();

    expect($options)->toBeInstanceOf(ProjectionOption::class)
        ->and($options)->toBeInstanceOf(JsonSerializable::class)
        ->and($options->getSignal())->toBeFalse()
        ->and($options->getCacheSize())->toBe(100)
        ->and($options->getBlockSize())->toBe(100)
        ->and($options->getSleep())->toBe([1, 10])
        ->and($options->getTimeout())->toBe(1)
        ->and($options->getLockout())->toBe(0)
        ->and($options->getLoadLimiter())->toBe(100)
        ->and($options->getSleepEmitterOnFirstCommit())->toBe(0)
        ->and($options->getRetries())->toBeEmpty()
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
            'sleep' => [1, 10],
            'lockout' => 0,
            'retries' => [],
            'detectionWindows' => null,
            'loadLimiter' => 100,
            'onlyOnceDiscovery' => false,
            'sleepEmitterOnFirstCommit' => 0,
        ]);
});
