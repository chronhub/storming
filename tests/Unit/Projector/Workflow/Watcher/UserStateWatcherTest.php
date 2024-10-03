<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Watcher;

use Storm\Projector\Factory\Component\UserState;

beforeEach(function () {
    $this->watcher = new UserState;
});

test('default instance', function () {
    expect($this->watcher->get())->toBeEmpty();
});

test('initialize state', function () {
    $this->watcher->init(fn () => ['foo' => 'bar']);

    expect($this->watcher->get())->toBe(['foo' => 'bar']);
});

test('initialize state with null', function () {
    $this->watcher->init(null);

    expect($this->watcher->get())->toBeEmpty();
});

test('initialize state with empty closure', function () {
    $this->watcher->init(fn () => null);

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
