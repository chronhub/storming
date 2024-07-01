<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Checkpoint;

use Closure;
use Mockery\MockInterface;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\CheckpointRecognition;
use Storm\Contract\Projector\GapRecognition;
use Storm\Projector\Checkpoint\CheckpointStore;
use Storm\Projector\Checkpoint\GapRules;
use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Checkpoint\StreamPoint;
use Storm\Projector\Exception\CheckpointViolation;

use function array_values;
use function count;

beforeEach(function () {
    $this->gapDetector = mock(GapRecognition::class);
    $this->rules = new GapRules();
    $this->clock = mock(SystemClock::class);

    $this->store = new CheckpointStore($this->gapDetector, $this->rules, $this->clock);
});

function mockCheckpointCreatedAt(int $expectedCalls = 1): Closure
{
    /** @phpstan-ignore-next-line  */
    return fn (SystemClock&MockInterface $clock) => $clock
        ->shouldReceive('generate')
        ->andReturn('2025-01-01 00:00:00')
        ->times($expectedCalls);
}

test('default instance', function () {
    expect($this->store)->toBeInstanceOf(CheckpointRecognition::class)
        ->and($this->store->toArray())->toBeEmpty()
        ->and($this->store->jsonSerialize())->toBeArray();
});

test('discover streams', function () {
    $this->clock->expects('generate')->andReturn('2025-01-01 00:00:00');

    $this->store->discover('stream-1');

    expect($this->store->toArray())->toHaveKey('stream-1');
});

test('does not duplicate stream on discover', function () {
    mockCheckpointCreatedAt()($this->clock);

    $this->store->discover('stream-1', 'stream-1');

    expect($this->store->toArray())->toHaveCount(1)
        ->and($this->store->toArray())->toHaveKey('stream-1');
});

test('insert checkpoint without gap', function () {
    mockCheckpointCreatedAt(2)($this->clock);

    $this->store->discover('stream-1');

    $streamPoint = new StreamPoint('stream-1', 1, '2021-01-01 00:00:00');
    $checkpoint = $this->store->insert($streamPoint);

    expect($checkpoint->streamName)->toBe('stream-1')
        ->and($checkpoint->position)->toBe(1)
        ->and($checkpoint->eventTime)->toBe('2021-01-01 00:00:00')
        ->and($checkpoint->createdAt)->toBe('2025-01-01 00:00:00')
        ->and($checkpoint->gaps)->toBeEmpty()
        ->and($checkpoint->gapType)->toBeNull();
});

test('insert checkpoint with gap', function () {
    mockCheckpointCreatedAt(2)($this->clock);
    $this->gapDetector->shouldReceive('gapType')->andReturn(GapType::IN_GAP);
    $this->gapDetector->shouldReceive('isRecoverable')->andReturn(false);

    $this->store->discover('stream-1');

    $streamPoint = new StreamPoint('stream-1', 2, '2021-01-01 00:00:00');
    $checkpoint = $this->store->insert($streamPoint);

    expect($checkpoint->streamName)->toBe('stream-1')
        ->and($checkpoint->position)->toBe(2)
        ->and($checkpoint->eventTime)->toBe('2021-01-01 00:00:00')
        ->and($checkpoint->createdAt)->toBe('2025-01-01 00:00:00')
        ->and($checkpoint->gaps)->toBe([1])
        ->and($checkpoint->gapType)->toBe(GapType::IN_GAP);
});

test('update checkpoints as array', function () {
    mockCheckpointCreatedAt(2)($this->clock);

    $this->store->discover('stream-1');

    $streamPoint = new StreamPoint('stream-1', 1, '2021-01-01 00:00:00');
    $this->store->insert($streamPoint);

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

test('raise exception when stream not found on updating', function () {
    expect($this->store->toArray())->toBeEmpty();

    $this->store->update([[
        'stream_name' => 'stream-1',
        'position' => 2,
        'event_time' => '2021-01-01 00:00:00',
        'created_at' => '2025-01-01 00:00:00',
        'gaps' => [],
        'gap_type' => null,
    ]]);
})->throws(CheckpointViolation::class, 'Checkpoint not tracked for stream stream-1');

test('check if gap is detected', function (bool $isGapDetected) {
    $this->gapDetector->shouldReceive('hasGap')->andReturn($isGapDetected)->once();

    expect($this->store->hasGap())->toBe($isGapDetected);
})->with([
    ['gap detected' => true],
    ['gap not detected' => false],
]);

test('sleep when gap is detected', function () {
    $this->gapDetector->shouldReceive('sleep')->once();

    $this->store->sleepWhenGap();
});

test('reset checkpoints and gap detection', function (array $streams) {
    mockCheckpointCreatedAt(count($streams) * 2)($this->clock);

    $this->gapDetector->shouldNotReceive('gapType');
    $this->gapDetector->shouldReceive('reset')->once();

    foreach ($streams as $stream) {
        $this->store->discover($stream);
        $streamPoint = new StreamPoint($stream, 1, '2021-01-01 00:00:00');
        $this->store->insert($streamPoint);
    }

    expect($this->store->toArray())->toHaveKeys(array_values($streams));

    $this->store->resets();

    expect($this->store->toArray())->toBeEmpty();
})->with([
    'two streams' => [['stream-1', 'stream-2']],
    'four streams' => [['stream-1', 'stream-2, stream-3', 'stream-4']],
]);
