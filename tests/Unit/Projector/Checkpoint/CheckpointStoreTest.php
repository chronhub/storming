<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Checkpoint;

use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\CheckpointRecognition;
use Storm\Contract\Projector\GapRecognition;
use Storm\Projector\Checkpoint\CheckpointCollection;
use Storm\Projector\Checkpoint\CheckpointStore;
use Storm\Projector\Checkpoint\GapRules;
use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Exception\CheckpointViolation;

beforeEach(function () {
    $this->clock = $this->createMock(SystemClock::class);
    $this->checkpoints = new CheckpointCollection($this->clock);
    $this->gapDetector = $this->createMock(GapRecognition::class);
    $this->rules = new GapRules();

    $this->store = new CheckpointStore($this->checkpoints, $this->gapDetector, $this->rules);
});

it('test default instance', function () {
    expect($this->store)->toBeInstanceOf(CheckpointRecognition::class)
        ->and($this->store->toArray())->toBeEmpty()
        ->and($this->store->jsonSerialize())->toBeArray()
        ->and($this->store->hasGap())->toBeFalse();
});

it('refresh streams', function () {
    $this->clock->expects($this->once())->method('generate')->willReturn('2025-01-01 00:00:00');

    $this->store->refreshStreams(['stream-1']);

    expect($this->store->toArray())->toHaveKey('stream-1');
});

it('insert checkpoint without gap', function () {
    $this->clock->expects($this->exactly(2))->method('generate')->willReturn('2025-01-01 00:00:00');

    $this->store->refreshStreams(['stream-1']);

    $checkpoint = $this->store->insert('stream-1', 1, '2021-01-01 00:00:00');

    expect($checkpoint->streamName)->toBe('stream-1')
        ->and($checkpoint->position)->toBe(1)
        ->and($checkpoint->eventTime)->toBe('2021-01-01 00:00:00')
        ->and($checkpoint->createdAt)->toBe('2025-01-01 00:00:00')
        ->and($checkpoint->gaps)->toBeEmpty()
        ->and($checkpoint->gapType)->toBeNull();
});

it('insert checkpoint with gap', function () {
    $this->clock->expects($this->exactly(3))->method('generate')->willReturn('2025-01-01 00:00:00');
    $this->gapDetector->expects($this->once())->method('gapType')->willReturn(GapType::IN_GAP);
    $this->gapDetector->expects($this->once())->method('isRecoverable')->willReturn(false);

    $this->store->refreshStreams(['stream-1']);

    $checkpoint = $this->store->insert('stream-1', 2, '2021-01-01 00:00:00');

    expect($checkpoint->streamName)->toBe('stream-1')
        ->and($checkpoint->position)->toBe(2)
        ->and($checkpoint->eventTime)->toBe('2021-01-01 00:00:00')
        ->and($checkpoint->createdAt)->toBe('2025-01-01 00:00:00')
        ->and($checkpoint->gaps)->toBe([1])
        ->and($checkpoint->gapType)->toBe(GapType::IN_GAP);
});

it('update checkpoints', function () {
    $this->clock->expects($this->exactly(2))->method('generate')->willReturn('2025-01-01 00:00:00');
    $this->store->refreshStreams(['stream-1']);
    $this->store->insert('stream-1', 1, '2021-01-01 00:00:00');

    $checkpointUpdated = [
        'stream_name' => 'stream-1',
        'position' => 2,
        'event_time' => '2021-01-01 00:00:00',
        'created_at' => '2025-01-01 00:00:00',
        'gaps' => [],
        'gap_type' => null,
    ];

    $this->store->update([$checkpointUpdated]);

    $checkpoint = $this->store->jsonSerialize()['stream-1'];

    expect($checkpointUpdated)->toBe($checkpoint);
});

it('raise exception when stream not found on updating', function () {
    expect($this->store->toArray())->toBeEmpty();

    $this->store->update([[
        'stream_name' => 'stream-1',
        'position' => 2,
        'event_time' => '2021-01-01 00:00:00',
        'created_at' => '2025-01-01 00:00:00',
        'gaps' => [],
        'gap_type' => null,
    ]]);
})->throws(CheckpointViolation::class, 'Stream stream-1 is not watched');

it('check if gap is detected', function (bool $isGapDetected) {
    $this->gapDetector->expects($this->once())->method('hasGap')->willReturn($isGapDetected);

    expect($this->store->hasGap())->toBe($isGapDetected);
})->with(['is gap detected' => [true, false]]);

it('sleep when gap is detected', function () {
    $this->gapDetector->expects($this->once())->method('sleep');

    $this->store->sleepWhenGap();
});

it('reset checkpoints and gap detection', function () {
    $this->clock->expects($this->exactly(4))->method('generate')->willReturn('2025-01-01 00:00:00');
    $this->gapDetector->expects($this->once())->method('reset');

    $this->store->refreshStreams(['stream-1']);
    $this->store->insert('stream-1', 1, '2021-01-01 00:00:00');
    $this->store->refreshStreams(['stream-2']);
    $this->store->insert('stream-2', 1, '2021-01-01 00:00:00');

    expect($this->store->toArray())->toHaveKey('stream-1')
        ->and($this->store->toArray())->toHaveKey('stream-2');

    $this->store->resets();

    expect($this->store->toArray())->toBeEmpty();
});
