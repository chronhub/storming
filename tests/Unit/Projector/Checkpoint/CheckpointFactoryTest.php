<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Checkpoint;

use Storm\Clock\PointInTime;
use Storm\Projector\Checkpoint\Checkpoint;
use Storm\Projector\Checkpoint\CheckpointFactory;
use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Checkpoint\StreamPoint;

dataset('stream name', ['stream1', 'stream2']);
dataset('position', [1, 50, 100]);
dataset('gaps', [[[]], [[2, 10]], [[10, 20]], [[20, 30]]]);
dataset('gap type', [null, GapType::IN_GAP, GapType::RECOVERABLE_GAP, GapType::UNRECOVERABLE_GAP]);
dataset('created at', ['2021-08-01T00:00:00.000000', '2030-08-01T00:00:00.000000']);
dataset('event time with null', [null, '2022-08-01T00:00:00.000000', '2060-08-01T00:00:00.000000']);
dataset('event time', [fn () => PointInTime::fromString('2022-08-01T00:00:00.000000'), '2022-08-01T00:00:00.000000', '2060-08-01T00:00:00.000000']);

test('new checkpoint', function (string $streamName, string $createdAt) {
    $checkpoint = CheckpointFactory::new($streamName, $createdAt);

    expect($checkpoint)->toBeInstanceOf(Checkpoint::class)
        ->and($checkpoint->streamName)->toBe($streamName)
        ->and($checkpoint->createdAt)->toBe($createdAt)
        ->and($checkpoint->position)->toBe(0)
        ->and($checkpoint->gaps)->toBe([])
        ->and($checkpoint->gapType)->toBeNull();
})->with('stream name', 'created at');

test('from', function (string $streamName, int $position, ?string $eventTime, string $createdAt, array $gaps, ?GapType $gapType) {
    $checkpoint = CheckpointFactory::from($streamName, $position, $eventTime, $createdAt, $gaps, $gapType);

    expect($checkpoint)->toBeInstanceOf(Checkpoint::class)
        ->and($checkpoint->streamName)->toBe($streamName)
        ->and($checkpoint->position)->toBe($position)
        ->and($checkpoint->eventTime)->toBe($eventTime)
        ->and($checkpoint->createdAt)->toBe($createdAt)
        ->and($checkpoint->gaps)->toBe($gaps)
        ->and($checkpoint->gapType)->toBe($gapType);
})->with('stream name', 'position', 'event time with null', 'created at', 'gaps', 'gap type');

test('from stream point', function (string $streamName, int $position, string|PointInTime $eventTime, string $createdAt, array $gaps, ?GapType $gapType) {
    $streamPoint = new StreamPoint($streamName, $position, $eventTime);
    $checkpoint = CheckpointFactory::fromStreamPoint($streamPoint, $createdAt, $gaps, $gapType);

    $expectedEventTime = $eventTime instanceof PointInTime
        ? $eventTime->format()
        : $eventTime;

    expect($checkpoint)->toBeInstanceOf(Checkpoint::class)
        ->and($checkpoint->streamName)->toBe($streamName)
        ->and($checkpoint->position)->toBe($position)
        ->and($checkpoint->eventTime)->toBe($expectedEventTime)
        ->and($checkpoint->createdAt)->toBe($createdAt)
        ->and($checkpoint->gaps)->toBe($gaps)
        ->and($checkpoint->gapType)->toBe($gapType);

})->with('stream name', 'position', 'event time', 'created at', 'gaps', 'gap type');

test('no gap', function (string $streamName, int $position, string|PointInTime $eventTime, string $createdAt, array $gaps, ?GapType $gapType) {
    if ($eventTime instanceof PointInTime) {
        $eventTime = $eventTime->format();
    }

    $checkpoint = CheckpointFactory::from($streamName, $position, $eventTime, $createdAt, $gaps, $gapType);
    $noGapCheckpoint = CheckpointFactory::noGap($checkpoint);

    expect($noGapCheckpoint)->toBeInstanceOf(Checkpoint::class)
        ->and($noGapCheckpoint->streamName)->toBe($streamName)
        ->and($noGapCheckpoint->eventTime)->toBe($eventTime)
        ->and($noGapCheckpoint->createdAt)->toBe($createdAt)
        ->and($noGapCheckpoint->position)->toBe($position)
        ->and($noGapCheckpoint->gaps)->toBe([])
        ->and($noGapCheckpoint->gapType)->toBeNull();
})->with('stream name', 'position', 'event time', 'created at', 'gaps', 'gap type');

test('from array', function (string $streamName, int $position, string|PointInTime $eventTime, string $createdAt, array $gaps, ?GapType $gapType) {
    if ($eventTime instanceof PointInTime) {
        $eventTime = $eventTime->format();
    }

    $checkpoint = CheckpointFactory::fromArray([
        'stream_name' => $streamName,
        'position' => $position,
        'event_time' => $eventTime,
        'created_at' => $createdAt,
        'gaps' => $gaps,
        'gap_type' => $gapType?->value ?? null,
    ]);

    expect($checkpoint)->toBeInstanceOf(Checkpoint::class)
        ->and($checkpoint->streamName)->toBe($streamName)
        ->and($checkpoint->position)->toBe($position)
        ->and($checkpoint->eventTime)->toBe($eventTime)
        ->and($checkpoint->createdAt)->toBe($createdAt)
        ->and($checkpoint->gaps)->toBe($gaps)
        ->and($checkpoint->gapType)->toBe($gapType);
})->with('stream name', 'position', 'event time', 'created at', 'gaps', 'gap type');
