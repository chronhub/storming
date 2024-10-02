<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Watcher;

use Storm\Projector\Factory\Component\EventStreamBatch;
use Storm\Projector\Stream\Iterator\MergeStreamIterator;
use Storm\Projector\Support\ExponentialSleep;
use Storm\Tests\Stubs\MergeStreamIteratorStub;

beforeEach(function () {
    $this->sleep = new ExponentialSleep(1000, 2, 4000);
    $this->watcher = new EventStreamBatch($this->sleep);
});

test('test new instance', function () {
    expect($this->sleep->getSleepingTime())->toBe(1000);
});

test('set iterator', function () {
    $iterator = (new MergeStreamIteratorStub)->getMergeStreams();

    $this->watcher->set($iterator);

    expect($this->sleep->getSleepingTime())->toBe(1000);

    $pulledIterator = $this->watcher->pull();
    expect($pulledIterator)->toBe($iterator)
        ->and($this->sleep->getSleepingTime())->toBe(1000);
});

test('set null iterator and increment sleeping time till max', function () {
    $this->watcher->set(null);
    expect($this->sleep->getSleepingTime())->toBe(2000);

    $this->watcher->set(null);
    expect($this->sleep->getSleepingTime())->toBe(4000);

    $this->watcher->set(null);
    expect($this->sleep->getSleepingTime())->toBe(4000);
});

test('set null iterator and reset sleeping time', function () {
    $this->watcher->set(null);
    expect($this->sleep->getSleepingTime())->toBe(2000);

    $this->watcher->set(null);
    expect($this->sleep->getSleepingTime())->toBe(4000);

    $this->watcher->set(null);
    expect($this->sleep->getSleepingTime())->toBe(4000);

    $this->watcher->set((new MergeStreamIteratorStub)->getMergeStreams());
    expect($this->sleep->getSleepingTime())->toBe(1000);
});

test('pull iterator', function () {
    $this->watcher->set((new MergeStreamIteratorStub)->getMergeStreams());

    expect($this->watcher->pull())->toBeInstanceOf(MergeStreamIterator::class)
        ->and($this->watcher->pull())->toBeNull();
});

test('sleep', function () {
    $this->watcher->set((new MergeStreamIteratorStub)->getMergeStreams());

    $this->watcher->sleep();
    expect($this->sleep->getSleepingTime())->toBe(1000);
});

test('sleep max', function () {
    $this->watcher->set(null);
    $this->watcher->set(null);
    $this->watcher->set(null);

    $this->watcher->sleep();
    expect($this->sleep->getSleepingTime())->toBe(4000);
});
