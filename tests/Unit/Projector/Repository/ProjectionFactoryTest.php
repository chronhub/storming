<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Repository;

use Storm\Projector\Repository\Projection;
use Storm\Projector\Repository\ProjectionFactory;
use Storm\Tests\Stubs\DummyProjectionModel;

beforeEach(function () {
    ProjectionFactory::$model = Projection::class;

    $this->data = [
        'name' => 'stream-1',
        'status' => 'status-1',
        'state' => 'state-1',
        'checkpoint' => 'checkpoint-1',
        'locked_until' => 'locked_until-1',
    ];
});

test('create new projection', function (string $stream, string $status) {
    $projection = ProjectionFactory::create($stream, $status);

    expect($projection)->toBeInstanceOf(Projection::class)
        ->and($projection->name())->toBe($stream)
        ->and($projection->status())->toBe($status)
        ->and($projection->state())->toBe('{}')
        ->and($projection->checkpoint())->toBe('{}')
        ->and($projection->lockedUntil())->toBeNull();
})->with([['stream-1', 'status-1'], ['stream-2', 'status-2']]);

test('create projection from array', function () {
    $projection = ProjectionFactory::make($this->data);

    expect($projection)->toBeInstanceOf(Projection::class)
        ->and($projection->name())->toBe($this->data['name'])
        ->and($projection->status())->toBe($this->data['status'])
        ->and($projection->state())->toBe($this->data['state'])
        ->and($projection->checkpoint())->toBe($this->data['checkpoint'])
        ->and($projection->lockedUntil())->toBe($this->data['locked_until']);

});

test('assume null when state, checkpoint or locked_until is missing', function (array $data, string $missingKey) {
    $projection = ProjectionFactory::fromArray($data)->jsonSerialize();

    $missingKey === 'locked_until'
        ? expect($projection['locked_until'])->toBeNull()
        : expect($projection[$missingKey])->toBe('{}');

})->with([
    [['name' => 'stream-1', 'status' => 'status-1', 'state' => 'state-1', 'checkpoint' => 'checkpoint-1'], 'locked_until'],
    [['name' => 'stream-1', 'status' => 'status-1', 'state' => 'state-1', 'locked_until' => 'locked_until-1'], 'checkpoint'],
    [['name' => 'stream-1', 'status' => 'status-1', 'checkpoint' => 'checkpoint-1', 'locked_until' => 'locked_until-1'], 'state'],
]);

test('create projection from object', function () {
    $data = (object) $this->data;

    $projection = ProjectionFactory::make($data);

    expect($projection)->toBeInstanceOf(Projection::class)
        ->and($projection->name())->toBe($data->name)
        ->and($projection->status())->toBe($data->status)
        ->and($projection->state())->toBe($data->state)
        ->and($projection->checkpoint())->toBe($data->checkpoint)
        ->and($projection->lockedUntil())->toBe($data->locked_until);
});

test('create projection with custom model', function () {
    ProjectionFactory::$model = DummyProjectionModel::class;

    $projection = ProjectionFactory::make($this->data);

    expect($projection)->toBeInstanceOf(DummyProjectionModel::class)
        ->and($projection->name())->toBe($this->data['name'])
        ->and($projection->status())->toBe($this->data['status'])
        ->and($projection->state())->toBe($this->data['state'])
        ->and($projection->checkpoint())->toBe($this->data['checkpoint'])
        ->and($projection->lockedUntil())->toBe($this->data['locked_until']);
});
