<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Watcher;

use Countable;
use Storm\Contract\Projector\TokenBucket;
use Storm\Projector\Workflow\Watcher\BatchStreamWatcher;

beforeEach(function () {
    $this->bucket = $this->createStub(TokenBucket::class);
    $this->watcher = new BatchStreamWatcher($this->bucket);
});

it('test new instance', function () {
    expect($this->watcher)->toBeInstanceOf(Countable::class)
        ->and($this->watcher->count())->toBe(0);
});

it('increment counter when no stream has been loaded', function () {
    expect($this->watcher->count())->toBe(0);

    $this->watcher->hasLoadedStreams(false);
    expect($this->watcher->count())->toBe(1);

    $this->watcher->hasLoadedStreams(false);
    expect($this->watcher->count())->toBe(2);
});

it('reset counter when streams has been loaded', function () {
    expect($this->watcher->count())->toBe(0);

    $this->watcher->hasLoadedStreams(false);
    expect($this->watcher->count())->toBe(1);

    $this->watcher->hasLoadedStreams(true);
    expect($this->watcher->count())->toBe(0);
});

it('consume token bucket while sleeping', function () {
    $this->bucket->method('consume')->willReturn(true);
    $this->bucket->method('getCapacity')->willReturn(2);

    $this->watcher->hasLoadedStreams(false);
    expect($this->watcher->count())->toBe(1);

    $this->watcher->sleep();

    expect($this->watcher->count())->toBe(1);
});

it('reset counter when it reaches the bucket capacity', function () {
    $this->bucket->method('consume')->willReturn(true);
    $this->bucket->method('getCapacity')->willReturn(2);

    $this->watcher->hasLoadedStreams(false);
    expect($this->watcher->count())->toBe(1);

    $this->watcher->sleep();
    expect($this->watcher->count())->toBe(1);

    $this->watcher->hasLoadedStreams(false);
    expect($this->watcher->count())->toBe(2);

    $this->watcher->sleep();
    expect($this->watcher->count())->toBe(0);
});
