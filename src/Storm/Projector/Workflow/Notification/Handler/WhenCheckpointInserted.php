<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Handler;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Checkpoint\Checkpoint;
use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Workflow\Notification\Checkpoint\CheckpointInserted;

final class WhenCheckpointInserted
{
    public function __invoke(NotificationHub $hub, CheckpointInserted $event, Checkpoint $checkpoint): void
    {
        if ($checkpoint->gapType instanceof GapType) {
            $listener = $checkpoint->gapType->value;

            $hub->notify($listener, $event->streamName, $event->streamPosition);
        }
    }
}
