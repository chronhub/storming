<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Support;

use Storm\Contract\Projector\ReadModel;
use Storm\Projector\Support\ReadModel\InMemoryReadModel;

beforeEach(function () {
    $this->readModel = new InMemoryReadModel();
});

test('default instance', function () {
    expect($this->readModel)->toBeInstanceOf(InMemoryReadModel::class)
        ->and($this->readModel)->toBeInstanceOf(ReadModel::class)
        ->and($this->readModel->isInitialized())->toBeTrue()
        ->and($this->readModel->getContainer())->toBeEmpty();
});

test('initialize', function () {
    expect($this->readModel->isInitialized())->toBeTrue();

    $this->readModel->initialize();

    expect($this->readModel->isInitialized())->toBeTrue();
});

test('insert ', function () {
    $this->readModel->initialize();

    $this->readModel->insert('id', ['foo' => 'bar']);
    $this->readModel->persist();

    expect($this->readModel->getContainer())->toBeArray()
        ->and($this->readModel->getContainer())->toHaveCount(1)
        ->and($this->readModel->getContainer()['id'])->toBeArray()
        ->and($this->readModel->getContainer()['id'])->toHaveCount(1)
        ->and($this->readModel->getContainer()['id']['foo'])->toBe('bar');
});

test('update', function () {
    expect($this->readModel->getContainer())->toBeEmpty()
        ->and($this->readModel->isInitialized())->toBeTrue();

    $this->readModel->insert('id', ['foo' => 'bar']);
    $this->readModel->persist();

    expect($this->readModel->getContainer())->toBe(['id' => ['foo' => 'bar']]);

    $this->readModel->update('id', 'foo', 'baz');
    $this->readModel->persist();

    expect($this->readModel->getContainer())->toBe(['id' => ['foo' => 'baz']]);
});

test('increment', function () {
    expect($this->readModel->getContainer())->toBeEmpty()
        ->and($this->readModel->isInitialized())->toBeTrue();

    $this->readModel->initialize();
    expect($this->readModel->isInitialized())->toBeTrue();

    $this->readModel->insert('id', ['foo' => 1]);
    $this->readModel->persist();

    expect($this->readModel->getContainer())->toBe(['id' => ['foo' => 1]]);

    $this->readModel->increment('id', 'foo', 1);
    $this->readModel->persist();

    expect($this->readModel->getContainer())->toBe(['id' => ['foo' => 2]]);
});

test('decrement', function () {
    expect($this->readModel->getContainer())->toBeEmpty()
        ->and($this->readModel->isInitialized())->toBeTrue();

    $this->readModel->insert('id', ['foo' => 1]);
    $this->readModel->persist();

    expect($this->readModel->getContainer())->toBe(['id' => ['foo' => 1]]);

    $this->readModel->decrement('id', 'foo', -1);
    $this->readModel->persist();

    expect($this->readModel->getContainer())->toBe(['id' => ['foo' => 0]]);
});

test('delete', function () {
    expect($this->readModel->getContainer())->toBeEmpty()
        ->and($this->readModel->isInitialized())->toBeTrue();

    $this->readModel->insert('id', ['foo' => 'bar']);
    $this->readModel->persist();

    expect($this->readModel->getContainer())->toBe(['id' => ['foo' => 'bar']]);

    $this->readModel->delete('id');
    $this->readModel->persist();

    expect($this->readModel->getContainer())->toBeEmpty();
});

test('reset', function () {
    expect($this->readModel->getContainer())->toBeEmpty()
        ->and($this->readModel->isInitialized())->toBeTrue();

    $this->readModel->initialize();
    expect($this->readModel->isInitialized())->toBeTrue();

    $this->readModel->insert('id', ['foo' => 'bar']);
    $this->readModel->persist();

    expect($this->readModel->getContainer())->toBe(['id' => ['foo' => 'bar']]);

    $this->readModel->reset();

    expect($this->readModel->getContainer())->toBeEmpty()
        ->and($this->readModel->isInitialized())->toBeTrue();
});

test('down', function () {
    expect($this->readModel->getContainer())->toBeEmpty()
        ->and($this->readModel->isInitialized())->toBeTrue();

    $this->readModel->insert('id', ['foo' => 'bar']);
    $this->readModel->persist();

    expect($this->readModel->getContainer())->toBe(['id' => ['foo' => 'bar']]);

    $this->readModel->down();

    expect($this->readModel->getContainer())->toBeEmpty()
        ->and($this->readModel->isInitialized())->toBeTrue();
});
