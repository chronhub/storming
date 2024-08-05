<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Option;

use JsonSerializable;
use Options\ProjectionOption;
use Storm\Projector\Options\DefaultOption;

test('default instance', function () {
    $option = new DefaultOption();

    expect($option)->toBeInstanceOf(ProjectionOption::class)
        ->and($option)->toBeInstanceOf(JsonSerializable::class)
        ->and($option->getSignal())->toBeFalse()
        ->and($option->getCacheSize())->toBe(1000)
        ->and($option->getBlockSize())->toBe(1000)
        ->and($option->getSleep())->toBe([10000, 5, 1000000])
        ->and($option->getTimeout())->toBe(10000)
        ->and($option->getLockout())->toBe(1000000)
        ->and($option->getLoadLimiter())->toBe(1000)
        ->and($option->getSleepEmitterOnFirstCommit())->toBe(1000)
        ->and($option->getOnlyOnceDiscovery())->toBeFalse()
        ->and($option->getRetries())->toBe([0, 5, 10, 25, 50, 100, 150, 200, 250, 300, 350, 400, 450, 500])
        ->and($option->getRecordGap())->toBeFalse()
        ->and($option->getDetectionWindows())->toBeNull();
});

test('set up retries as array', function () {
    $retries = [0, 5, 10, 25, 50, 100, 150, 200, 250, 300, 350, 400, 450, 500];
    $option = new DefaultOption(retries: $retries);

    expect($option->getRetries())->toBe($retries);
});

test('set up retries as string to be used by range function', function (string $retries, array $expected) {
    $option = new DefaultOption(retries: $retries);

    expect($option->getRetries())->toBe($expected);
})->with([
    ['1,5,1', [1, 2, 3, 4, 5]],
    ['10,30,5', [10, 15, 20, 25, 30]],
]);

test('json serialize default instance', function () {
    $option = new DefaultOption();

    expect($option->jsonSerialize())
        ->toBe([
            'signal' => false,
            'cacheSize' => 1000,
            'blockSize' => 1000,
            'timeout' => 10000,
            'sleep' => [10000, 5, 1000000],
            'lockout' => 1000000,
            'retries' => [0, 5, 10, 25, 50, 100, 150, 200, 250, 300, 350, 400, 450, 500],
            'recordGap' => false,
            'detectionWindows' => null,
            'loadLimiter' => 1000,
            'onlyOnceDiscovery' => false,
            'sleepEmitterOnFirstCommit' => 1000,
        ]);
});
