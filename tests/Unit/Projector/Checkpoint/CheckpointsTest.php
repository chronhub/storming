<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Checkpoint;

use Countable;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Storm\Projector\Checkpoint\CheckpointFactory;
use Storm\Projector\Checkpoint\Checkpoints;
use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Exception\CheckpointViolation;

dataset('should record gaps', [[true], [false]]);
dataset('gap type', [[null], [GapType::IN_GAP], [GapType::RECOVERABLE_GAP], [GapType::UNRECOVERABLE_GAP]]);

test('default instance', function (bool $recordGaps) {
    $checkpoints = new Checkpoints($recordGaps);

    expect($checkpoints)->toBeInstanceOf(JsonSerializable::class)
        ->and($checkpoints)->toBeInstanceOf(Arrayable::class)
        ->and($checkpoints)->toBeInstanceOf(Countable::class)
        ->and($checkpoints->recordGaps)->toBe($recordGaps)
        ->and($checkpoints->toArray())->toBe([])
        ->and($checkpoints->jsonSerialize())->toBe([])
        ->and($checkpoints->count())->toBe(0);
})->with('should record gaps');

test('save checkpoint', function (bool $recordGaps) {
    $checkpoints = new Checkpoints($recordGaps);

    $checkpoint = CheckpointFactory::from('stream1', 0, null, '2022-01-01', [], null);

    expect($checkpoints->has('stream1'))->toBeFalse()
        ->and($checkpoints->save($checkpoint))->toBe($checkpoint)
        ->and($checkpoints->has('stream1'))->toBeTrue();
})->with('should record gaps');

test('save checkpoint override checkpoint', function (bool $recordGaps) {
    $checkpoints = new Checkpoints($recordGaps);

    $checkpoint = CheckpointFactory::from('stream1', 0, null, '2022-01-01', [], null);

    expect($checkpoints->has('stream1'))->toBeFalse()
        ->and($checkpoints->save($checkpoint))->toBe($checkpoint)
        ->and($checkpoints->has('stream1'))->toBeTrue();

    $checkpoint = CheckpointFactory::from('stream1', 1, null, '2022-01-02', [], null);

    expect($checkpoints->save($checkpoint))->toBe($checkpoint)
        ->and($checkpoints->has('stream1'))->toBeTrue()
        ->and($checkpoints->get('stream1')->jsonSerialize())->toBe($checkpoint->jsonSerialize());
})->with('should record gaps');

test('refresh checkpoint', function (bool $recordGaps) {
    $checkpoints = new Checkpoints($recordGaps);

    $checkpoint = CheckpointFactory::from('stream1', 0, null, '2022-01-01', [], null);

    expect($checkpoints->has('stream1'))->toBeFalse()
        ->and($checkpoints->save($checkpoint))->toBe($checkpoint)
        ->and($checkpoints->has('stream1'))->toBeTrue();

    $checkpoint = CheckpointFactory::from('stream1', 1, null, '2022-01-02', [], null);

    expect($checkpoints->refresh($checkpoint))->toBe($checkpoint)
        ->and($checkpoints->has('stream1'))->toBeTrue()
        ->and($checkpoints->get('stream1')->jsonSerialize())->toBe($checkpoint->jsonSerialize());
})->with('should record gaps');

test('refresh checkpoint with gaps', function () {
    $checkpoints = new Checkpoints(true);

    $checkpoint = CheckpointFactory::from('stream1', 10, null, '2022-01-01', [2, 3], null);

    expect($checkpoints->has('stream1'))->toBeFalse()
        ->and($checkpoints->save($checkpoint))->toBe($checkpoint)
        ->and($checkpoints->has('stream1'))->toBeTrue();

    $checkpoint = CheckpointFactory::from('stream1', 25, null, '2022-01-02', [2, 3, 24], null);

    expect($checkpoints->refresh($checkpoint))->toBe($checkpoint)
        ->and($checkpoints->has('stream1'))->toBeTrue()
        ->and($checkpoints->get('stream1')->jsonSerialize())->toBe($checkpoint->jsonSerialize());
});

test('raise checkpoint violation when refreshing checkpoint with gaps', function () {
    $checkpoints = new Checkpoints(false);

    $checkpoint = CheckpointFactory::from('stream1', 15, null, '2022-01-01', [], null);

    expect($checkpoints->has('stream1'))->toBeFalse()
        ->and($checkpoints->save($checkpoint))->toBe($checkpoint)
        ->and($checkpoints->has('stream1'))->toBeTrue();

    $checkpoint = CheckpointFactory::from('stream1', 150, null, '2022-01-02', [10, 30], null);

    $checkpoints->refresh($checkpoint);
})->throws(CheckpointViolation::class, 'Recording gaps is disabled for stream stream1 and cannot be updated with gaps');

test('get checkpoint', function (bool $recordGaps) {
    $checkpoints = new Checkpoints(false);

    $checkpoint = CheckpointFactory::from('stream1', 15, null, '2022-01-01', [], null);

    expect($checkpoints->has('stream1'))->toBeFalse()
        ->and($checkpoints->save($checkpoint))->toBe($checkpoint)
        ->and($checkpoints->has('stream1'))->toBeTrue()
        ->and($checkpoints->get('stream1'))->toBe($checkpoint);
})->with('should record gaps');

test('raise checkpoint violation when getting non tracked stream', function () {
    $checkpoints = new Checkpoints(false);

    $checkpoint = CheckpointFactory::from('stream1', 15, null, '2022-01-01', [], null);

    expect($checkpoints->has('stream1'))->toBeFalse()
        ->and($checkpoints->save($checkpoint))->toBe($checkpoint)
        ->and($checkpoints->has('stream1'))->toBeTrue();

    $checkpoints->get('stream2');
})->throws(CheckpointViolation::class, 'Checkpoint not tracked for stream stream2');

test('flush checkpoints', function (bool $recordGaps) {
    $checkpoints = new Checkpoints($recordGaps);

    $checkpoint = CheckpointFactory::from('stream1', 15, null, '2022-01-01', [], null);

    expect($checkpoints->has('stream1'))->toBeFalse()
        ->and($checkpoints->save($checkpoint))->toBe($checkpoint)
        ->and($checkpoints->has('stream1'))->toBeTrue()
        ->and($checkpoints)->toHaveCount(1);

    $checkpoints->flush();

    expect($checkpoints->has('stream1'))->toBeFalse()
        ->and($checkpoints)->toHaveCount(0);
})->with('should record gaps');

test('serialize to json with recorded gaps', function (?GapType $gapType) {
    $checkpoints = new Checkpoints(true);

    $checkpoint = CheckpointFactory::from('stream1', 10, null, '2022-01-01', [2, 3], $gapType);

    expect($checkpoints->has('stream1'))->toBeFalse()
        ->and($checkpoints->save($checkpoint))->toBe($checkpoint)
        ->and($checkpoints->has('stream1'))->toBeTrue();

    $checkpoint2 = CheckpointFactory::from('stream2', 25, null, '2022-01-02', [24], $gapType);

    expect($checkpoints->save($checkpoint2))->toBe($checkpoint2)
        ->and($checkpoints->has('stream2'))->toBeTrue();

    $serialized = $checkpoints->jsonSerialize();

    expect($serialized)->toBe([
        'stream1' => $checkpoint->jsonSerialize(),
        'stream2' => $checkpoint2->jsonSerialize(),
    ])
        ->and($serialized['stream1']['gaps'])->toBe([2, 3])
        ->and($serialized['stream2']['gaps'])->toBe([24])
        ->and($serialized['stream1']['gap_type'])->toBe($gapType->value)
        ->and($serialized['stream2']['gap_type'])->toBe($gapType->value);

})->with([[GapType::IN_GAP], [GapType::RECOVERABLE_GAP], [GapType::UNRECOVERABLE_GAP]]);

test('serialize to json with filtered gaps', function (?GapType $gapType) {
    $checkpoints = new Checkpoints(false);

    $checkpoint = CheckpointFactory::from('stream1', 10, null, '2022-01-01', [2, 3], $gapType);

    expect($checkpoints->has('stream1'))->toBeFalse()
        ->and($checkpoints->save($checkpoint))->toBe($checkpoint)
        ->and($checkpoints->has('stream1'))->toBeTrue();

    $checkpoint2 = CheckpointFactory::from('stream2', 25, null, '2022-01-02', [24], $gapType);

    expect($checkpoints->save($checkpoint2))->toBe($checkpoint2)
        ->and($checkpoints->has('stream2'))->toBeTrue();

    $serialized = $checkpoints->jsonSerialize();

    expect($serialized)->not->toBe([
        'stream1' => $checkpoint->jsonSerialize(),
        'stream2' => $checkpoint2->jsonSerialize(),
    ])
        ->and($serialized['stream1']['gaps'])->toBe([])
        ->and($serialized['stream2']['gaps'])->toBe([])
        ->and($serialized['stream1']['gap_type'])->toBeNull()
        ->and($serialized['stream2']['gap_type'])->toBeNull();
})->with('gap type');

test('to array does not depends on recorded gaps', function (bool $recordGaps, ?GapType $gapType) {
    $checkpoints = new Checkpoints($recordGaps);

    $checkpoint = CheckpointFactory::from('stream1', 10, null, '2022-01-01', [2, 3], $gapType);

    expect($checkpoints->has('stream1'))->toBeFalse()
        ->and($checkpoints->save($checkpoint))->toBe($checkpoint)
        ->and($checkpoints->has('stream1'))->toBeTrue();

    $checkpoint2 = CheckpointFactory::from('stream2', 25, null, '2022-01-02', [24], $gapType);

    expect($checkpoints->save($checkpoint2))->toBe($checkpoint2)
        ->and($checkpoints->has('stream2'))->toBeTrue();

    $checkpoint3 = CheckpointFactory::from('stream3', 25, null, '2022-01-02', [], $gapType);
    expect($checkpoints->save($checkpoint3))->toBe($checkpoint3)
        ->and($checkpoints->has('stream3'))->toBeTrue();

    $toArray = $checkpoints->toArray();

    expect($toArray)->toBe([
        'stream1' => $checkpoint,
        'stream2' => $checkpoint2,
        'stream3' => $checkpoint3,
    ]);

})->with('should record gaps', 'gap type');
