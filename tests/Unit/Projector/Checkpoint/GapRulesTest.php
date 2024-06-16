<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Checkpoint;

use Storm\Projector\Checkpoint\Checkpoint;
use Storm\Projector\Checkpoint\GapRules;
use Storm\Projector\Exception\CheckpointViolation;

beforeEach(function () {
    $this->rules = new GapRules();
});

function getCheckpointStub(int $lastPosition, array $gaps): Checkpoint
{
    return new Checkpoint('stream-1', $lastPosition, null, '2024-01-01 00:00:00', $gaps);
}

it('validate current gap', function (int $lastPosition, int $currentPosition) {
    $checkpoint = getCheckpointStub($lastPosition, []);
    $instance = $this->rules->mustBeGap($checkpoint, $currentPosition);

    expect($instance)->toBe($this->rules);
})
    ->with([
        [0, 1],
        [1, 2],
        [2, 3],
        [3, 4],
    ]);

it('raise exception if current position is not a gap', function (int $lastPosition, int $currentPosition) {
    $checkpoint = getCheckpointStub($lastPosition, []);
    $this->rules->mustBeGap($checkpoint, $currentPosition);
})
    ->with([
        [1, 0],
        [1, 1],
        [2, 2],
        [4, 3],
    ])
    ->throws(CheckpointViolation::class, 'Invalid gap position: no gap or checkpoints are outdated');

it('validate if gap is not already recorded', function (array $gaps, int $lastPosition) {
    $checkpoint = getCheckpointStub($lastPosition, []);
    $instance = $this->rules->shouldNotAlreadyBeRecorded($checkpoint, $gaps);

    expect($instance)->toBe($this->rules);
})
    ->with([
        [[1], 0],
        [[1], 2],
        [[1, 2], 0],
        [[1, 2], 3],
    ]);

it('raise exception if gap is already recorded', function (array $gaps, int $lastPosition) {
    $checkpoint = getCheckpointStub($lastPosition, [1]);

    $this->rules->shouldNotAlreadyBeRecorded($checkpoint, $gaps);
})
    ->with([
        [[1], 1],
        [[1, 2], 1],
        [[1, 2], 2],
        [[1, 2], 3],
    ])
    //fixMe partial exception message
    ->throws(CheckpointViolation::class, 'already recorded');

it('validate when previous gap is empty', function () {
    $checkpoint = getCheckpointStub(0, []);
    $instance = $this->rules->mustBeGreaterThanPreviousGaps($checkpoint, [1]);

    expect($instance)->toBe($this->rules);
});

it('validate gap is greater than previous recorded gaps', function () {
    $checkpoint = getCheckpointStub(0, [1, 2]);
    $instance = $this->rules->mustBeGreaterThanPreviousGaps($checkpoint, [3]);

    expect($instance)->toBe($this->rules);
});

it('raise exception if gap is lower than previous recorded gaps', function (array $gaps, int $lastPosition) {
    $checkpoint = getCheckpointStub($lastPosition, [1, 2]);

    $this->rules->mustBeGreaterThanPreviousGaps($checkpoint, $gaps);
})
    ->with([
        [[1], 3],
        [[1, 2], 3],
        [[1, 2], 2],
        [[1, 2], 1],
    ])
    ->throws(CheckpointViolation::class, 'Cannot record gaps which are lower than previous recorded gaps');
