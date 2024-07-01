<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Checkpoint;

use Storm\Projector\Checkpoint\GapRules;
use Storm\Projector\Exception\CheckpointViolation;
use Storm\Tests\Stubs\CheckpointStub;

beforeEach(function () {
    $this->rules = new GapRules();
    $this->checkpointStub = new CheckpointStub();
});

test('validate current gap', function (int $lastPosition, int $currentPosition) {
    $checkpoint = $this->checkpointStub->with(position: $lastPosition);
    $instance = $this->rules->mustBeGap($checkpoint, $currentPosition);

    expect($instance)->toBe($this->rules);
})->with([[0, 1], [1, 2], [2, 3], [3, 4]]);

test('raise exception if current position is not a gap', function (int $lastPosition, int $currentPosition) {
    $checkpoint = $this->checkpointStub->with(position: $lastPosition);
    $this->rules->mustBeGap($checkpoint, $currentPosition);
})
    ->with([[1, 0], [1, 1], [2, 2], [4, 3], [10, 3]])
    ->throws(CheckpointViolation::class, 'Invalid gap position: no gap or checkpoints are outdated');

test('validate if gap is not already recorded', function (array $gaps, int $lastPosition) {
    $checkpoint = $this->checkpointStub->with(position: $lastPosition);
    $instance = $this->rules->shouldNotAlreadyBeRecorded($checkpoint, $gaps);

    expect($instance)->toBe($this->rules);
})
    ->with([
        [[1], 0],
        [[1], 2],
        [[1, 2], 0],
        [[1, 2], 3],
    ]);

test('raise exception if gap is already recorded', function (array $gaps, int $lastPosition) {
    $checkpoint = $this->checkpointStub->with(position: $lastPosition, gaps: [1]);

    try {
        $this->rules->shouldNotAlreadyBeRecorded($checkpoint, $gaps);
    } catch (CheckpointViolation $e) {
        expect($e->getMessage())->toBe("Gap at position 1 already recorded for stream $checkpoint->streamName");
    }
})
    ->with([
        [[1], 1],
        [[1, 2], 1],
        [[1, 2], 2],
        [[1, 2], 3],
    ]);

test('validate when previous gap is empty', function () {
    $checkpoint = $this->checkpointStub->with(position: 0);

    $instance = $this->rules->mustBeGreaterThanPreviousGaps($checkpoint, [1]);

    expect($instance)->toBe($this->rules);
});

test('validate gap is greater than previous recorded gaps', function () {
    $checkpoint = $this->checkpointStub->with(position: 0, gaps: [1, 2]);

    $instance = $this->rules->mustBeGreaterThanPreviousGaps($checkpoint, [3]);

    expect($instance)->toBe($this->rules);
});

test('raise exception if gap is lower than previous recorded gaps', function (array $gaps, int $lastPosition) {
    $checkpoint = $this->checkpointStub->with(position: $lastPosition, gaps: [1, 2]);

    $this->rules->mustBeGreaterThanPreviousGaps($checkpoint, $gaps);
})
    ->with([
        [[1], 3],
        [[1, 2], 3],
        [[1, 2], 2],
        [[1, 2], 1],
    ])
    ->throws(CheckpointViolation::class, 'Cannot record gaps which are lower than previous recorded gaps');
