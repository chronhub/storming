<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Checkpoint;

use Storm\Clock\PointInTime;
use Storm\Projector\Checkpoint\StreamPoint;
use Storm\Projector\Exception\CheckpointViolation;

dataset('stream names', ['', 'stream-1', 'stream-2']);
dataset('stream positions', [1, 10]);
dataset('event time', [
    ['as string' => '2020-01-01T00:00:00.000000'],
    ['as point in time' => PointInTime::fromString('2020-01-01T00:00:00.000000')],
]);

test('default instance', function ($streamName, $streamPosition, $eventTime) {
    $streamPoint = new StreamPoint($streamName, $streamPosition, $eventTime);

    expect($streamPoint->name)->toBe($streamName)
        ->and($streamPoint->position)->toBe($streamPosition)
        ->and($streamPoint->eventTime)->toBe($eventTime);
})->with('stream names', 'stream positions', 'event time');

test('raise exception when position is not a positive integer', function (int $streamPosition) {
    new StreamPoint('stream-1', $streamPosition, '2020-01-01T00:00:00.000000');
})
    ->with([[0, [-1], [-10]]])
    ->throws(CheckpointViolation::class);
