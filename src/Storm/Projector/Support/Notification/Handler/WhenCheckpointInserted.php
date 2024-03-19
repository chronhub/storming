<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Notification\Handler;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Checkpoint\Checkpoint;
use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Support\Notification\Checkpoint\CheckpointInserted;
use Storm\Projector\Support\Notification\Checkpoint\GapDetected;
use Storm\Projector\Support\Notification\Checkpoint\RecoverableGapDetected;
use Storm\Projector\Support\Notification\Checkpoint\UnrecoverableGapDetected;

class WhenCheckpointInserted
{
    public function __invoke(NotificationHub $hub, CheckpointInserted $event, Checkpoint $checkpoint): void
    {
        $listener = match ($checkpoint->type) {
            GapType::IN_GAP => GapDetected::class,
            GapType::RECOVERABLE_GAP => RecoverableGapDetected::class,
            GapType::UNRECOVERABLE_GAP => UnrecoverableGapDetected::class,
            default => null,
        };

        if ($listener !== null) {
            $hub->notify($listener, $event->streamName, $event->streamPosition);
        }
    }
}
