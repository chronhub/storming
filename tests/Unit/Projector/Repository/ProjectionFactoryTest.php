<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Repository;

use Storm\Projector\Repository\ProjectionFactory;

it('create new projection', function (string $stream, string $status) {
    $projection = ProjectionFactory::create($stream, $status);

    expect($projection->name())->toBe($stream)
        ->and($projection->status())->toBe($status)
        ->and($projection->state())->toBe('{}')
        ->and($projection->checkpoint())->toBe('{}')
        ->and($projection->lockedUntil())->toBeNull();
})->with([['stream-1', 'status-1'], ['stream-2', 'status-2']]);

it('create projection from array', function () {
    $data = [
        'name' => 'stream-1',
        'status' => 'status-1',
        'state' => 'state-1',
        'checkpoint' => 'checkpoint-1',
        'locked_until' => 'locked_until-1',
    ];

    $projection = ProjectionFactory::make($data);

    expect($projection->name())->toBe($data['name'])
        ->and($projection->status())->toBe($data['status'])
        ->and($projection->state())->toBe($data['state'])
        ->and($projection->checkpoint())->toBe($data['checkpoint'])
        ->and($projection->lockedUntil())->toBe($data['locked_until']);
});

it('assume null when state, checkpoint or locked_until is missing', function (array $data, string $missingKey) {
    $projection = ProjectionFactory::fromArray($data)->jsonSerialize();

    if ($missingKey === 'locked_until') {
        expect($projection['locked_until'])->toBeNull();
    } else {
        expect($projection[$missingKey])->toBe('{}');
    }
})->with([
    [['name' => 'stream-1', 'status' => 'status-1', 'state' => 'state-1', 'checkpoint' => 'checkpoint-1'], 'locked_until'],
    [['name' => 'stream-1', 'status' => 'status-1', 'state' => 'state-1', 'locked_until' => 'locked_until-1'], 'checkpoint'],
    [['name' => 'stream-1', 'status' => 'status-1', 'checkpoint' => 'checkpoint-1', 'locked_until' => 'locked_until-1'], 'state'],
]);

it('create projection from object', function () {
    $data = (object) [
        'name' => 'stream-1',
        'status' => 'status-1',
        'state' => 'state-1',
        'checkpoint' => 'checkpoint-1',
        'locked_until' => 'locked_until-1',
    ];

    $projection = ProjectionFactory::make($data);

    expect($projection->name())->toBe($data->name)
        ->and($projection->status())->toBe($data->status)
        ->and($projection->state())->toBe($data->state)
        ->and($projection->checkpoint())->toBe($data->checkpoint)
        ->and($projection->lockedUntil())->toBe($data->locked_until);
});
