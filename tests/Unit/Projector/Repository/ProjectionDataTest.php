<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Repository;

use Storm\Projector\Store\Data\CreateData;
use Storm\Projector\Store\Data\PersistData;
use Storm\Projector\Store\Data\ProjectionData;
use Storm\Projector\Store\Data\ReleaseData;
use Storm\Projector\Store\Data\ResetData;
use Storm\Projector\Store\Data\StartAgainData;
use Storm\Projector\Store\Data\StartData;
use Storm\Projector\Store\Data\StopData;
use Storm\Projector\Store\Data\UpdateLockData;
use Storm\Projector\Store\Data\UpdateStatusData;

test('create data', function () {
    $data = new CreateData('idle');

    expect($data)->toBeInstanceOf(ProjectionData::class)
        ->and($data->status)->toBe('idle')
        ->and($data->toArray())->toBe(['status' => 'idle'])
        ->and($data->jsonSerialize())->toBe(['status' => 'idle']);
});

test('persist data', function () {
    $data = new PersistData('user state', 'checkpoints', 'locked until');

    expect($data)->toBeInstanceOf(ProjectionData::class)
        ->and($data->state)->toBe('user state')
        ->and($data->checkpoint)->toBe('checkpoints')
        ->and($data->lockedUntil)->toBe('locked until');

    $dataAsArray = $data->toArray();

    expect($dataAsArray)->toBe([
        'state' => 'user state',
        'checkpoint' => 'checkpoints',
        'locked_until' => 'locked until',
    ])->and($dataAsArray)->toBe($data->jsonSerialize());
});

test('release data', function () {
    $data = new ReleaseData('idle', null);

    expect($data)->toBeInstanceOf(ProjectionData::class)
        ->and($data->status)->toBe('idle')
        ->and($data->lockedUntil)->toBeNull();

    $dataAsArray = $data->toArray();

    expect($dataAsArray)->toBe([
        'status' => 'idle',
        'locked_until' => null,
    ])->and($dataAsArray)->toBe($data->jsonSerialize());
});

test('reset data', function () {
    $data = new ResetData('idle', 'user state', 'checkpoints');

    expect($data)->toBeInstanceOf(ProjectionData::class)
        ->and($data->status)->toBe('idle')
        ->and($data->state)->toBe('user state')
        ->and($data->checkpoint)->toBe('checkpoints');

    $dataAsArray = $data->toArray();

    expect($dataAsArray)->toBe([
        'status' => 'idle',
        'state' => 'user state',
        'checkpoint' => 'checkpoints',
    ])->and($dataAsArray)->toBe($data->jsonSerialize());
});

test('start again data', function () {
    $data = new StartAgainData('idle', 'locked until');

    expect($data)->toBeInstanceOf(ProjectionData::class)
        ->and($data->status)->toBe('idle')
        ->and($data->lockedUntil)->toBe('locked until');

    $dataAsArray = $data->toArray();

    expect($dataAsArray)->toBe([
        'status' => 'idle',
        'locked_until' => 'locked until',
    ])->and($dataAsArray)->toBe($data->jsonSerialize());

});

test('start data', function () {
    $data = new StartData('running', 'locked until');

    expect($data)->toBeInstanceOf(ProjectionData::class)
        ->and($data->status)->toBe('running')
        ->and($data->lockedUntil)->toBe('locked until');

    $dataAsArray = $data->toArray();

    expect($dataAsArray)->toBe([
        'status' => 'running',
        'locked_until' => 'locked until',
    ])->and($dataAsArray)->toBe($data->jsonSerialize());
});

test('stop data', function () {
    $data = new StopData('idle', 'user state', 'checkpoints', 'locked until');

    expect($data)->toBeInstanceOf(ProjectionData::class)
        ->and($data->status)->toBe('idle')
        ->and($data->state)->toBe('user state')
        ->and($data->checkpoint)->toBe('checkpoints')
        ->and($data->lockedUntil)->toBe('locked until');

    $dataAsArray = $data->toArray();

    expect($dataAsArray)->toBe([
        'status' => 'idle',
        'state' => 'user state',
        'checkpoint' => 'checkpoints',
        'locked_until' => 'locked until',
    ])->and($dataAsArray)->toBe($data->jsonSerialize());
});

test('update lock data', function () {
    $data = new UpdateLockData('locked until');

    expect($data)->toBeInstanceOf(ProjectionData::class)
        ->and($data->lockedUntil)->toBe('locked until')
        ->and($data->toArray())->toBe(['locked_until' => 'locked until'])
        ->and($data->jsonSerialize())->toBe(['locked_until' => 'locked until']);
});

test('update status data', function () {
    $data = new UpdateStatusData('running');

    expect($data)->toBeInstanceOf(ProjectionData::class)
        ->and($data->status)->toBe('running')
        ->and($data->toArray())->toBe(['status' => 'running'])
        ->and($data->jsonSerialize())->toBe(['status' => 'running']);
});
