<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Watcher;

use Countable;
use Storm\Contract\Projector\TokenBucket;
use Storm\Projector\Workflow\Watcher\BatchStreamWatcher;

beforeEach(function () {
    $this->bucket = mock(TokenBucket::class);
    $this->watcher = new BatchStreamWatcher($this->bucket);
});

test('test new instance', function () {
    expect($this->watcher)->toBeInstanceOf(Countable::class)
        ->and($this->watcher->count())->toBe(0);
});

test('increment counter when no stream has been loaded', function () {
    expect($this->watcher->count())->toBe(0);

    $this->watcher->hasLoadedStreams(false);
    expect($this->watcher->count())->toBe(1);

    $this->watcher->hasLoadedStreams(false);
    expect($this->watcher->count())->toBe(2);
});

test('reset counter when streams has been loaded', function () {
    expect($this->watcher->count())->toBe(0);

    $this->watcher->hasLoadedStreams(false);
    expect($this->watcher->count())->toBe(1);

    $this->watcher->hasLoadedStreams(true);
    expect($this->watcher->count())->toBe(0);
});

test('consume token bucket while sleeping', function () {
    $this->bucket->expects('consume')->andReturn(true);
    $this->bucket->expects('getCapacity')->andReturn(2);

    $this->watcher->hasLoadedStreams(false);
    expect($this->watcher->count())->toBe(1);

    $this->watcher->sleep();

    expect($this->watcher->count())->toBe(1);
});

test('reset counter when it reaches the bucket capacity', function () {
    $this->bucket->expects('consume')->andReturn(true)->twice();
    $this->bucket->expects('getCapacity')->andReturn(2)->twice();

    $this->watcher->hasLoadedStreams(false);
    expect($this->watcher->count())->toBe(1);

    $this->watcher->sleep();
    expect($this->watcher->count())->toBe(1);

    $this->watcher->hasLoadedStreams(false);
    expect($this->watcher->count())->toBe(2);

    $this->watcher->sleep();
    expect($this->watcher->count())->toBe(0);
});
