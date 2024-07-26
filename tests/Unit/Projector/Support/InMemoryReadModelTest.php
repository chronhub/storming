<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Support;

use Storm\Contract\Projector\ReadModel;
use Storm\Projector\Support\ReadModel\InMemoryReadModel;

use function abs;

beforeEach(function () {
    $this->readModel = new InMemoryReadModel();
});

test('default instance', function () {
    expect($this->readModel)->toBeInstanceOf(InMemoryReadModel::class)
        ->and($this->readModel)->toBeInstanceOf(ReadModel::class)
        ->and($this->readModel->isInitialized())->toBeFalse()
        ->and($this->readModel->getContainer())->toBeEmpty();
});

test('initialize', function () {
    expect($this->readModel->isInitialized())->toBeFalse();

    $this->readModel->initialize();

    expect($this->readModel->isInitialized())->toBeTrue();
});

test('insert ', function () {
    $this->readModel->initialize();

    $this->readModel->stack('insert', 'id', ['foo' => 'bar']);
    $this->readModel->persist();

    expect($this->readModel->getContainer())->toBeArray()
        ->and($this->readModel->getContainer())->toHaveCount(1)
        ->and($this->readModel->getContainer()['id'])->toBeArray()
        ->and($this->readModel->getContainer()['id'])->toHaveCount(1)
        ->and($this->readModel->getContainer()['id']['foo'])->toBe('bar');
});

test('update', function () {
    expect($this->readModel->getContainer())->toBeEmpty()
        ->and($this->readModel->isInitialized())->toBeFalse();

    $this->readModel->initialize();
    expect($this->readModel->isInitialized())->toBeTrue();

    $this->readModel->stack('insert', 'id', ['foo' => 'bar']);
    $this->readModel->persist();

    expect($this->readModel->getContainer())->toBe(['id' => ['foo' => 'bar']]);

    $this->readModel->stack('update', 'id', 'foo', 'baz');
    $this->readModel->persist();

    expect($this->readModel->getContainer())->toBe(['id' => ['foo' => 'baz']]);
});

test('increment and force positive number', function (int|float $value) {
    expect($this->readModel->getContainer())->toBeEmpty()
        ->and($this->readModel->isInitialized())->toBeFalse();

    $this->readModel->initialize();
    expect($this->readModel->isInitialized())->toBeTrue();

    $this->readModel->stack('insert', 'id', ['foo' => 1]);
    $this->readModel->persist();

    expect($this->readModel->getContainer())->toBe(['id' => ['foo' => 1]]);

    $this->readModel->stack('increment', 'id', 'foo', $value);
    $this->readModel->persist();

    expect($this->readModel->getContainer())->toBe(['id' => ['foo' => abs($value) + 1]]);
})
    ->with([[1], [-1], [0.1], [-0.1]]);

test('decrement and force negative number', function (int|float $value) {
    expect($this->readModel->getContainer())->toBeEmpty()
        ->and($this->readModel->isInitialized())->toBeFalse();

    $this->readModel->initialize();
    expect($this->readModel->isInitialized())->toBeTrue();

    $this->readModel->stack('insert', 'id', ['foo' => 1]);
    $this->readModel->persist();

    expect($this->readModel->getContainer())->toBe(['id' => ['foo' => 1]]);

    $this->readModel->stack('decrement', 'id', 'foo', $value);
    $this->readModel->persist();

    expect($this->readModel->getContainer())->toBe(['id' => ['foo' => 1 - abs($value)]]);
})->with([[1], [-1], [0.1], [-0.1]]);

test('delete', function () {
    expect($this->readModel->getContainer())->toBeEmpty()
        ->and($this->readModel->isInitialized())->toBeFalse();

    $this->readModel->initialize();
    expect($this->readModel->isInitialized())->toBeTrue();

    $this->readModel->stack('insert', 'id', ['foo' => 'bar']);
    $this->readModel->persist();

    expect($this->readModel->getContainer())->toBe(['id' => ['foo' => 'bar']]);

    $this->readModel->stack('delete', 'id');
    $this->readModel->persist();

    expect($this->readModel->getContainer())->toBeEmpty();
});

test('reset', function () {
    expect($this->readModel->getContainer())->toBeEmpty()
        ->and($this->readModel->isInitialized())->toBeFalse();

    $this->readModel->initialize();
    expect($this->readModel->isInitialized())->toBeTrue();

    $this->readModel->stack('insert', 'id', ['foo' => 'bar']);
    $this->readModel->persist();

    expect($this->readModel->getContainer())->toBe(['id' => ['foo' => 'bar']]);

    $this->readModel->reset();

    expect($this->readModel->getContainer())->toBeEmpty()
        ->and($this->readModel->isInitialized())->toBeFalse();
});

test('down', function () {
    expect($this->readModel->getContainer())->toBeEmpty()
        ->and($this->readModel->isInitialized())->toBeFalse();

    $this->readModel->initialize();
    expect($this->readModel->isInitialized())->toBeTrue();

    $this->readModel->stack('insert', 'id', ['foo' => 'bar']);
    $this->readModel->persist();

    expect($this->readModel->getContainer())->toBe(['id' => ['foo' => 'bar']]);

    $this->readModel->down();

    expect($this->readModel->getContainer())->toBeEmpty()
        ->and($this->readModel->isInitialized())->toBeFalse();
});
