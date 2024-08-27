<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Checkpoint;

use Storm\Projector\Checkpoint\Checkpoint;
use Storm\Projector\Checkpoint\GapRules;
use Storm\Projector\Exception\CheckpointViolation;
use Storm\Tests\Stubs\CheckpointStub;

use function array_merge;
use function range;

beforeEach(function () {
    $this->rules = new GapRules();
    $this->checkpointStub = new CheckpointStub();
});

dataset('must be gap', [[1, 3], [1, 2], [2, 3], [3, 10]]);
dataset('not a gap', [[1, 0], [1, -1], [1, 0], [1, 1], [2, 2], [4, 3], [10, 3]]);
dataset('gap not already recorded', [
    [5, 10, [1]],
    [5, 10, [2, 4]],
    [5, 10, [4]],
    [25, 30, [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]],
]);
dataset('gap already recorded', [
    [5, 10, [7]],
    [5, 10, [9]],
    [1, 10, [4]],
    [10, 30, [9, 14, 25]],
    [25, 30, [29]],
]);
dataset('gap greater than previous', [
    [20, 30, [1, 10, 15]],
    [120, 170, [110]],
]);

dataset('gap lower than previous', [
    [25, 30, [32]],
    [120, 170, [200]],
]);

function calculateGaps(Checkpoint $lastCheckpoint, int $currentPosition): array
{
    return array_merge(
        $lastCheckpoint->gaps,
        range($lastCheckpoint->position + 1, $currentPosition - 1)
    );
}

test('validate gaps', function (int $lastPosition, int $gapPosition) {
    $lastCheckpoint = $this->checkpointStub->with(position: $lastPosition);
    $gaps = $this->rules->mergeGaps($lastCheckpoint, $gapPosition);

    expect($gaps)->toBe(calculateGaps($lastCheckpoint, $gapPosition));
})->with('must be gap');

test('raise exception if current position is not a gap', function (int $lastPosition, int $gapPosition) {
    $checkpoint = $this->checkpointStub->with(position: $lastPosition);
    $this->rules->mergeGaps($checkpoint, $gapPosition);
})
    ->with('not a gap')
    ->throws(CheckpointViolation::class, 'Invalid gap position: no gap or checkpoints are outdated');

test('validate if gap is not already recorded', function (int $lastPosition, int $gapPosition, array $previousGaps) {
    $checkpoint = $this->checkpointStub->with(position: $lastPosition, gaps: $previousGaps);

    $gapsRange = $this->rules->mergeGaps($checkpoint, $gapPosition);

    expect($gapsRange)->toBe(calculateGaps($checkpoint, $gapPosition));
})
    ->with('gap not already recorded');

test('raise exception if gap is already recorded', function (int $lastPosition, int $gapPosition, array $previousGaps) {
    $checkpoint = $this->checkpointStub->with(position: $lastPosition, gaps: $previousGaps);

    try {
        $this->rules->mergeGaps($checkpoint, $gapPosition);
    } catch (CheckpointViolation $e) {
        expect($e->getMessage())->toBe("Gap at position $gapPosition already recorded for stream $checkpoint->streamName");
    }
})
    ->with('gap already recorded');

test('validate gap is greater than previous recorded gaps', function (int $lastPosition, int $gapPosition, array $previousGaps) {
    $checkpoint = $this->checkpointStub->with(position: $lastPosition, gaps: $previousGaps);

    $gapsRange = $this->rules->mergeGaps($checkpoint, $gapPosition);

    expect($gapsRange)->toBe(calculateGaps($checkpoint, $gapPosition));
})->with('gap greater than previous');

test('raise exception if gap is lower than previous recorded gaps', function (int $lastPosition, int $gapPosition, array $previousGaps) {
    $checkpoint = $this->checkpointStub->with(position: $lastPosition, gaps: $previousGaps);

    $this->rules->mergeGaps($checkpoint, $gapPosition);
})
    ->with('gap lower than previous')
    ->throws(CheckpointViolation::class, 'Cannot record gaps which are lower than previous recorded gaps');
