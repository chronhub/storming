<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Checkpoint;

use Closure;
use DateTimeImmutable;
use Illuminate\Support\Collection;
use Storm\Contract\Clock\SystemClock;
use Storm\Projector\Checkpoint\Checkpoint;
use Storm\Projector\Checkpoint\CheckpointCollection;
use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Exception\CheckpointViolation;

beforeEach(function () {
    $this->clock = $this->createMock(SystemClock::class);
    $this->checkpoints = new CheckpointCollection($this->clock);
});

dataset('stream names', ['stream-1', 'stream-2']);
dataset('positions', [1, 2, 3]);
dataset('event times', ['2024-05-01 00:00:00', DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2024-05-01 00:00:00')]);
dataset('gaps', [[[]], [[1, 2]]]);
dataset('gap types', [null, GapType::IN_GAP, GapType::RECOVERABLE_GAP, GapType::UNRECOVERABLE_GAP]);

function expectCreatedAt(string $createdAt = '2024-01-01 00:00:00', int $times = 1): Closure
{
    return fn ($that) => $that->clock->expects($that->exactly($times))->method('generate')->willReturn($createdAt);
}

function assertDefaultCheckpoint(Checkpoint $checkpoint, ?string $createdAt): void
{
    expect($checkpoint->position)->toBe(0)
        ->and($checkpoint->eventTime)->toBeNull()
        ->and($checkpoint->createdAt)->toBe($createdAt ?? '2024-01-01 00:00:00')
        ->and($checkpoint->gaps)->toBeEmpty()
        ->and($checkpoint->gapType)->toBeNull();
}

it('test default instance', function () {
    expect($this->checkpoints->all())->toBeInstanceOf(Collection::class)
        ->and($this->checkpoints->all())->toBeEmpty()
        ->and($this->checkpoints->has('stream-1'))->toBeFalse();
});

it('discover streams and insert new checkpoint', function () {
    expectCreatedAt(times: 2)($this);

    $this->checkpoints->onDiscover('stream-1', 'stream-2');

    expect($this->checkpoints->all())->toHaveCount(2)
        ->and($this->checkpoints->has('stream-1'))->toBeTrue()
        ->and($this->checkpoints->has('stream-2'))->toBeTrue();

    $this->checkpoints->all()->each(function (Checkpoint $checkpoint) {
        assertDefaultCheckpoint($checkpoint, null);
    });
});

it('flush all checkpoints', function () {
    expectCreatedAt(times: 3)($this);

    $this->checkpoints->onDiscover('stream-1', 'stream-2', 'stream-3');

    expect($this->checkpoints->all())->toHaveCount(3);

    $this->checkpoints->flush();

    expect($this->checkpoints->all())->toBeEmpty();
});

it('get last checkpoint', function () {
    expectCreatedAt()($this);

    $this->checkpoints->onDiscover('stream-1');

    $checkpoint = $this->checkpoints->last('stream-1');

    assertDefaultCheckpoint($checkpoint, null);
});

it('raise exception when getting last checkpoint for unknown stream', function () {
    $this->checkpoints->last('unknown-stream');
})->throws(CheckpointViolation::class, 'Checkpoint not found for stream unknown-stream');

it('create new checkpoint without inserting', function (string $streamName, int $position, string|DateTimeImmutable $eventTime, array $gaps, ?GapType $gapType) {
    expectCreatedAt()($this);

    if ($eventTime instanceof DateTimeImmutable) {
        $this->clock->expects($this->once())->method('format')->willReturn('2024-05-01 00:00:00');
    }

    $checkpoint = $this->checkpoints->newCheckpoint($streamName, $position, $eventTime, $gaps, $gapType);

    expect($checkpoint->streamName)->toBe($streamName)
        ->and($checkpoint->position)->toBe($position)
        ->and($checkpoint->createdAt)->toBe('2024-01-01 00:00:00')
        ->and($checkpoint->eventTime)->toBe('2024-05-01 00:00:00')
        ->and($checkpoint->gaps)->toBe($gaps)
        ->and($checkpoint->gapType)->toBe($gapType)
        ->and($this->checkpoints->all())->toBeEmpty();

})
    ->with('stream names', 'positions', 'event times', 'gaps', 'gap types');

it('raise exception when event time is null and position is no zero on new checkpoint', function (int $position) {
    $this->checkpoints->newCheckpoint('stream-1', $position, null, [], null);
})
    ->with(['positions' => [1, 10, 30]])
    ->throws(CheckpointViolation::class, 'Stream event time must be a valid date when position is not zero for stream stream-1');

it('insert new checkpoint instance', function (string $streamName, int $position, string|DateTimeImmutable $eventTime, array $gaps, ?GapType $gapType) {
    expectCreatedAt(times: 2)($this);

    if ($eventTime instanceof DateTimeImmutable) {
        $this->clock->expects($this->exactly(2))->method('format')->willReturn('2024-05-01 00:00:00');
    }

    $checkpoint = $this->checkpoints->newCheckpoint($streamName, $position, $eventTime, $gaps, $gapType);
    expect($this->checkpoints->all())->toBeEmpty();

    $checkpointInserted = $this->checkpoints->next($checkpoint, $position, $eventTime, $gapType);

    expect($checkpointInserted->streamName)->toBe($streamName)
        ->and($checkpointInserted->position)->toBe($position)
        ->and($checkpoint->eventTime)->toBe('2024-05-01 00:00:00')
        ->and($checkpointInserted->createdAt)->toBe('2024-01-01 00:00:00')
        ->and($checkpointInserted->gaps)->toBe($gaps)
        ->and($checkpointInserted->gapType)->toBe($gapType)
        ->and($this->checkpoints->all()->count())->toBe(1)
        ->and($checkpointInserted)->not()->toBe($checkpoint)
        ->and($this->checkpoints->last($streamName))->toBe($checkpointInserted);

})
    ->with('stream names', 'positions', 'event times', 'gaps', 'gap types');

it('update checkpoint', function () {
    expectCreatedAt()($this);

    $this->checkpoints->onDiscover('stream-1');

    $checkpoint = $this->checkpoints->last('stream-1');

    $this->checkpoints->update('stream-1', $checkpoint);

    expect($this->checkpoints->last('stream-1'))->toBe($checkpoint);
});

it('raise exception when updating unknown stream', function () {
    $checkpoint = new Checkpoint('unknown-stream', 0, null, '2024-01-01 00:00:00', [], null);

    $this->checkpoints->update('unknown-stream', $checkpoint);
})->throws(CheckpointViolation::class, 'Checkpoint not found for stream unknown-stream');
