<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Watcher;

use Countable;
use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Workflow\Watcher\BatchCounterWatcher;

use function method_exists;

it('test new instance', function (int $limit) {
    $watcher = new BatchCounterWatcher($limit);

    expect($watcher)->toBeInstanceOf(Countable::class)
        ->and($watcher->limit)->toBe($limit)
        ->and($watcher->count())->toBe(0)
        ->and($watcher->isReset())->toBeTrue()
        ->and($watcher->isReached())->toBeFalse()
        ->and(method_exists($watcher, 'subscribe'))->toBeFalse();
})->with(['valid limit' => [1, 10, 100]]);

it('raise exception when limit is less than 1', function (int $limit) {
    new BatchCounterWatcher($limit);
})
    ->with(['invalid limit' => [-10, -1, 0]])
    ->throws(InvalidArgumentException::class, 'Batch counter limit must be greater than 0');

it('test increment', function () {
    $watcher = new BatchCounterWatcher(2);
    $watcher->increment();

    expect($watcher->count())->toBe(1)
        ->and($watcher->isReset())->toBeFalse()
        ->and($watcher->isReached())->toBeFalse();
});

it('assert limit is reached', function () {
    $watcher = new BatchCounterWatcher(2);
    $watcher->increment();
    $watcher->increment();

    expect($watcher->count())->toBe(2)
        ->and($watcher->isReset())->toBeFalse()
        ->and($watcher->isReached())->toBeTrue();
});

it('test reset', function () {
    $watcher = new BatchCounterWatcher(2);
    $watcher->increment();
    $watcher->reset();

    expect($watcher->count())->toBe(0)
        ->and($watcher->isReset())->toBeTrue()
        ->and($watcher->isReached())->toBeFalse();
});
