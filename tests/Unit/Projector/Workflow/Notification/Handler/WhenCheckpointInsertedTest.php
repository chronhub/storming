<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Notification\Handler;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Checkpoint\Checkpoint;
use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Workflow\Notification\Checkpoint\CheckpointInserted;
use Storm\Projector\Workflow\Notification\Handler\WhenCheckpointInserted;

beforeEach(function () {
    $this->hub = mock(NotificationHub::class);
});

test('notify the listener when a checkpoint is inserted', function (int $streamPosition, GapType $gapType) {
    $event = new CheckpointInserted('stream-1', $streamPosition, 'createdAt');

    $checkpoint = new Checkpoint(
        streamName: 'stream-1',
        position: $streamPosition,
        eventTime: null,
        createdAt: 'createdAt',
        gaps: [],
        gapType: $gapType
    );

    $handler = new WhenCheckpointInserted();
    $this->hub->expects('notify')->with($gapType->value, 'stream-1', $streamPosition);

    $handler($this->hub, $event, $checkpoint);
})
    ->with([
        ['stream position of 1' => 1],
        ['stream position of 2' => 2],
        ['stream position of 50' => 50],
    ])
    ->with([
        'in gap' => [GapType::IN_GAP],
        'recoverable gap' => [GapType::RECOVERABLE_GAP],
        'unrecoverable gap' => [GapType::UNRECOVERABLE_GAP],
    ]);

test('does not notify the listener when the checkpoint is not a gap', function () {
    $event = new CheckpointInserted('stream-1', 2, 'createdAt');

    $checkpoint = new Checkpoint(
        streamName: 'stream-1',
        position: 1,
        eventTime: null,
        createdAt: 'createdAt',
        gaps: [],
        gapType: null
    );

    $handler = new WhenCheckpointInserted();
    $this->hub->shouldNotReceive('notify');

    $handler($this->hub, $event, $checkpoint);
});
