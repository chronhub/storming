<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Handler;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Checkpoint\ShouldSnapshotCheckpoint;
use Storm\Projector\Workflow\Notification\Batch\BatchReset;
use Storm\Projector\Workflow\Notification\Checkpoint\CheckpointInserted;
use Storm\Projector\Workflow\Notification\Cycle\CycleIncremented;
use Storm\Projector\Workflow\Notification\Cycle\CycleRenewed;
use Storm\Projector\Workflow\Notification\Cycle\CycleReset;
use Storm\Projector\Workflow\Notification\MasterCounter\MasterCounterReset;
use Storm\Projector\Workflow\Notification\Sprint\IsSprintTerminated;
use Storm\Projector\Workflow\Notification\Sprint\SprintTerminated;
use Storm\Projector\Workflow\Notification\Stream\NewEventStreamReset;
use Storm\Projector\Workflow\Notification\Stream\StreamEventAckedReset;
use Storm\Projector\Workflow\Notification\Timer\TimeReset;

final class WhenCycleRenewed
{
    public function __invoke(NotificationHub $hub, CycleRenewed $event): void
    {
        $this->notifySprintTerminated($hub);

        $this->notifyCycledEnded($hub);

        $this->flushWatcher($hub);
    }

    private function notifySprintTerminated(NotificationHub $hub): void
    {
        $hub->notifyWhen(
            $hub->expect(IsSprintTerminated::class),
            fn (NotificationHub $hub) => $hub->notify(SprintTerminated::class)
        );
    }

    private function flushWatcher(NotificationHub $hub): void
    {
        // reset every cycle
        $hub->notifyMany(BatchReset::class, StreamEventAckedReset::class, NewEventStreamReset::class);

        // reset only when sprint is terminated
        if ($hub->expect(IsSprintTerminated::class)) {
            $hub->notifyMany(TimeReset::class, MasterCounterReset::class);
        }
    }

    private function notifyCycledEnded(NotificationHub $hub): void
    {
        $isSprintTerminated = $hub->expect(IsSprintTerminated::class);

        $hub->notifyWhen(
            $isSprintTerminated,
            fn (NotificationHub $hub) => $hub->notify(CycleReset::class),
            fn (NotificationHub $hub) => $hub->notify(CycleIncremented::class),
        );

        // required when rerun projection
        if ($isSprintTerminated) {
            $hub->forgetListener(ShouldSnapshotCheckpoint::class);
            $hub->forgetListener(CheckpointInserted::class);
        }
    }
}
