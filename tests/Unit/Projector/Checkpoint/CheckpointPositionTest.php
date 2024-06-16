<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Checkpoint;

use Storm\Projector\Checkpoint\CheckpointPosition;
use Storm\Projector\Exception\InvalidArgumentException;

it('test new instance and accept zero and positive integers', function (int $position) {
    $instance = CheckpointPosition::fromInteger($position);

    expect($instance->toInteger())->toBe($position)
        ->and($instance->value)->toBe($position);
})->with([[0], [1], [2], [3], [4]]);

it('raise exception if position is less than zero', function (int $position) {
    CheckpointPosition::fromInteger($position);
})->with([[-1], [-2], [-3], [-4], [-5]])
    ->throws(InvalidArgumentException::class, 'Checkpoint position must be greater or equals than zero');

it('raise exception when position is zero', function () {
    $instance = CheckpointPosition::fromInteger(0);

    expect($instance->toInteger())->toBe(0)->and($instance->value)->toBe(0);

    $this->expectExceptionMessage('Checkpoint position must be positive integer');

    $instance->assertPositive();
});
