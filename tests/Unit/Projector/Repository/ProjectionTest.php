<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Repository;

use JsonSerializable;
use Storm\Contract\Projector\ProjectionModel;
use Storm\Projector\Store\Projection;

use function json_encode;

dataset('streams', ['stream-1', 'stream-2']);
dataset('statuses', ['run', 'stop']);
dataset('state', [null, '{}', '{"1"}']);
dataset('checkpoint', [null, '{}', '{"1"}']);
dataset('locked until', [null, '2022-01-01 00:00:00', 'datetime']);

test('default instance', function (
    string $stream,
    string $status,
    ?string $state,
    ?string $checkpoint,
    ?string $lockedUntil
) {
    $projection = new Projection($stream, $status, $state, $checkpoint, $lockedUntil);

    expect($projection)->toBeInstanceOf(ProjectionModel::class)
        ->and($projection->name())->toBe($stream)
        ->and($projection->status())->toBe($status)
        ->and($projection->state())->toBe($state ?? '{}')
        ->and($projection->checkpoint())->toBe($checkpoint ?? '{}')
        ->and($projection->lockedUntil())->toBe($lockedUntil);
})->with('streams', 'statuses', 'state', 'checkpoint', 'locked until');

test('serialize to json', function (
    string $stream,
    string $status,
    ?string $state,
    ?string $checkpoint,
    ?string $lockedUntil
) {
    $projection = new Projection($stream, $status, $state, $checkpoint, $lockedUntil);

    expect($projection)->toBeInstanceOf(JsonSerializable::class)
        ->and(json_encode($projection))->toBe(json_encode([
            'name' => $stream,
            'status' => $status,
            'state' => $state ?? '{}',
            'checkpoint' => $checkpoint ?? '{}',
            'locked_until' => $lockedUntil,
        ]));
})->with('streams', 'statuses', 'state', 'checkpoint', 'locked until');
