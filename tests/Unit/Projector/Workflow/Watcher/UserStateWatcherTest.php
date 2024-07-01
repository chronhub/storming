<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Watcher;

use Storm\Projector\Workflow\Watcher\UserStateWatcher;

beforeEach(function () {
    $this->watcher = new UserStateWatcher();
});

test('default instance', function () {
    expect($this->watcher->get())->toBeEmpty();
});

test('put state', function () {
    $this->watcher->put(['foo' => 'bar']);

    expect($this->watcher->get())->toBe(['foo' => 'bar']);
});

test('reset state', function () {
    $this->watcher->put(['foo' => 'bar']);
    expect($this->watcher->get())->toBe(['foo' => 'bar']);

    $this->watcher->reset();
    expect($this->watcher->get())->toBeEmpty();
});

test('override state', function () {
    $this->watcher->put(['foo' => 'bar']);
    expect($this->watcher->get())->toBe(['foo' => 'bar']);

    $this->watcher->put(['bar' => 'baz']);
    expect($this->watcher->get())->toBe(['bar' => 'baz']);
});

test('put empty state', function () {
    $this->watcher->put(['foo' => 'bar']);
    expect($this->watcher->get())->toBe(['foo' => 'bar']);

    $this->watcher->put([]);
    expect($this->watcher->get())->toBeEmpty();
});
