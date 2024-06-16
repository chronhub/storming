<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Notification\Handler;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Checkpoint\Checkpoint;
use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Workflow\Notification\Checkpoint\CheckpointInserted;
use Storm\Projector\Workflow\Notification\Handler\WhenCheckpointInserted;

beforeEach(function () {
    $this->hub = $this->createMock(NotificationHub::class);
    $this->event = new CheckpointInserted('stream-1', 2, 'createdAt');
});

it('notify the listener when a checkpoint is inserted', function (GapType $gapType) {
    $checkpoint = new Checkpoint('stream-1', 1, null, 'createdAt', [], $gapType);

    $handler = new WhenCheckpointInserted();

    $this->hub->expects($this->once())
        ->method('notify')
        ->with($gapType->value, 'stream-1', 2);

    $handler($this->hub, $this->event, $checkpoint);
})->with([
    'in gap' => [GapType::IN_GAP],
    'recoverable gap' => [GapType::RECOVERABLE_GAP],
    'unrecoverable gap' => [GapType::UNRECOVERABLE_GAP],
]);

it('does not notify the listener when the checkpoint is not a gap', function () {
    $checkpoint = new Checkpoint('stream-1', 1, null, 'createdAt', [], null);

    $handler = new WhenCheckpointInserted();

    $this->hub->expects($this->never())->method('notify');

    $handler($this->hub, $this->event, $checkpoint);
});
