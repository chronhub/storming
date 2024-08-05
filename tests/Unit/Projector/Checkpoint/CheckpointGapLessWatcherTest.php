<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Checkpoint;

use Checkpoint\CheckpointRecognition;
use Closure;
use LogicException;
use Mockery\MockInterface;
use Storm\Contract\Clock\SystemClock;
use Storm\Projector\Checkpoint\Checkpoints;
use Storm\Projector\Checkpoint\StreamPoint;
use Storm\Projector\Exception\CheckpointViolation;
use Storm\Projector\Workflow\Component\InMemoryCheckpoint;

use function array_values;
use function count;
use function method_exists;

beforeEach(function () {
    $this->clock = mock(SystemClock::class);
    $this->checkpoints = new Checkpoints(false);
    $this->watcher = new InMemoryCheckpoint($this->checkpoints, $this->clock);
});

function mockCheckpointGapLessCreatedAt(int $expectedCalls = 1): Closure
{
    return fn (SystemClock&MockInterface $clock) => $clock
        ->shouldReceive('generate')
        ->andReturn('2025-01-01 00:00:00')
        ->times($expectedCalls);
}

test('cannot be used with record gaps enabled', function () {
    $this->checkpoints = new Checkpoints(true);

    new InMemoryCheckpoint($this->checkpoints, $this->clock);
})->throws(LogicException::class, 'CheckpointGapLessWatcher cannot be used with record gaps enabled');

test('default instance', function () {
    expect($this->watcher)->toBeInstanceOf(CheckpointRecognition::class)
        ->and($this->watcher->toArray())->toBeEmpty()
        ->and($this->watcher->jsonSerialize())->toBeArray()
        ->and(method_exists($this->watcher, 'subscribe'))->toBeFalse();
});

test('track streams', function () {
    $this->clock->expects('generate')->andReturn('2025-01-01 00:00:00');

    $this->watcher->track('stream1');

    expect($this->watcher->toArray())->toHaveKey('stream1');
});

test('does not duplicate stream on track', function () {
    mockCheckpointGapLessCreatedAt(2)($this->clock);

    $this->watcher->track('stream1', 'stream1');

    expect($this->watcher->toArray())->toHaveCount(1)
        ->and($this->watcher->toArray())->toHaveKey('stream1');
});

test('insert no gap checkpoint without gap detection', function () {
    mockCheckpointGapLessCreatedAt(2)($this->clock);

    $this->watcher->track('stream1');

    $streamPoint = new StreamPoint('stream1', 1, '2021-01-01 00:00:00');
    $checkpoint = $this->watcher->record($streamPoint);

    expect($checkpoint->streamName)->toBe('stream1')
        ->and($checkpoint->position)->toBe(1)
        ->and($checkpoint->eventTime)->toBe('2021-01-01 00:00:00')
        ->and($checkpoint->createdAt)->toBe('2025-01-01 00:00:00')
        ->and($checkpoint->gaps)->toBeEmpty()
        ->and($checkpoint->gapType)->toBeNull();
});

test('insert gap checkpoint without gap detection', function () {
    mockCheckpointGapLessCreatedAt(2)($this->clock);

    $this->watcher->track('stream1');

    expect($this->watcher->hasGap())->toBeFalse();

    $streamPoint = new StreamPoint('stream1', 10, '2021-01-01 00:00:00');
    $checkpoint = $this->watcher->record($streamPoint);

    expect($checkpoint->streamName)->toBe('stream1')
        ->and($checkpoint->position)->toBe(10)
        ->and($checkpoint->eventTime)->toBe('2021-01-01 00:00:00')
        ->and($checkpoint->createdAt)->toBe('2025-01-01 00:00:00')
        ->and($checkpoint->gaps)->toBeEmpty()
        ->and($checkpoint->gapType)->toBeNull()
        ->and($this->watcher->hasGap())->toBeFalse();
});

test('update checkpoints as array', function () {
    mockCheckpointGapLessCreatedAt(2)($this->clock);

    $this->watcher->track('stream1');

    $streamPoint = new StreamPoint('stream1', 1, '2021-01-01 00:00:00');
    $this->watcher->record($streamPoint);

    $checkpointUpdated = [
        'stream_name' => 'stream1',
        'position' => 2,
        'event_time' => '2021-01-01 00:00:00',
        'created_at' => '2025-01-01 00:00:00',
        'gaps' => [],
        'gap_type' => null,
    ];

    $this->watcher->update([$checkpointUpdated]);

    $checkpoint = $this->watcher->jsonSerialize()['stream1'];

    expect($checkpointUpdated)->toBe($checkpoint);
});

test('raise exception when stream not found on updating', function () {
    expect($this->watcher->toArray())->toBeEmpty();

    $this->watcher->update([[
        'stream_name' => 'stream1',
        'position' => 2,
        'event_time' => '2021-01-01 00:00:00',
        'created_at' => '2025-01-01 00:00:00',
        'gaps' => [],
        'gap_type' => null,
    ]]);
})->throws(CheckpointViolation::class, 'Checkpoint not tracked for stream stream1');

test('has gap always returns false', function () {
    expect($this->watcher->hasGap())->toBeFalse();
});

test('reset checkpoints', function (array $streams) {
    mockCheckpointGapLessCreatedAt(count($streams) * 2)($this->clock);

    foreach ($streams as $stream) {
        $this->watcher->track($stream);

        $streamPoint = new StreamPoint($stream, 1, '2021-01-01 00:00:00');

        $this->watcher->record($streamPoint);
    }

    expect($this->watcher->toArray())->toHaveKeys(array_values($streams));

    $this->watcher->resets();

    expect($this->watcher->toArray())->toBeEmpty();
})->with([
    'two streams' => [['stream1', 'stream2']],
    'four streams' => [['stream1', 'stream2, stream3', 'stream4']],
]);
