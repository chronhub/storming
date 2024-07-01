<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Watcher;

use Storm\Projector\Workflow\Watcher\MasterEventCounterWatcher;

use function method_exists;

beforeEach(function () {
    $this->watcher = new MasterEventCounterWatcher();
});

test('default instance', function () {
    expect($this->watcher->current())->toBe(0)
        ->and($this->watcher->isDoNotReset())->toBeFalse()
        ->and(method_exists($this->watcher, 'subscribe'))->toBeFalse();
});

test('increment counter', function () {
    $this->watcher->increment();
    expect($this->watcher->current())->toBe(1);
});

test('reset counter', function () {
    expect($this->watcher->isDoNotReset())->toBeFalse();

    $this->watcher->increment();
    $this->watcher->reset();

    expect($this->watcher->current())->toBe(0);
});

test('call do not reset', function () {
    $this->watcher->doNotReset(true);
    expect($this->watcher->isDoNotReset())->toBeTrue();

    $this->watcher->increment();
    $this->watcher->increment();

    expect($this->watcher->current())->toBe(2);

    $this->watcher->reset();

    expect($this->watcher->current())->toBe(2);
});
