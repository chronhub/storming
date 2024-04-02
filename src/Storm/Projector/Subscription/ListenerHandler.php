<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\Checkpoint\CheckpointInserted;
use Storm\Projector\Workflow\Notification\Cycle\CycleBegan;
use Storm\Projector\Workflow\Notification\Cycle\CycleRenewed;
use Storm\Projector\Workflow\Notification\Handler\WhenBatchLoaded;
use Storm\Projector\Workflow\Notification\Handler\WhenCheckpointInserted;
use Storm\Projector\Workflow\Notification\Handler\WhenCycleBegin;
use Storm\Projector\Workflow\Notification\Handler\WhenCycleRenewed;
use Storm\Projector\Workflow\Notification\Handler\WhenEventStreamDiscovered;
use Storm\Projector\Workflow\Notification\Stream\EventStreamDiscovered;
use Storm\Projector\Workflow\Notification\Stream\StreamIteratorSet;

final class ListenerHandler
{
    public static function listen(NotificationHub $hub): void
    {
        $hub->addListeners([
            CycleBegan::class => WhenCycleBegin::class,
            CycleRenewed::class => WhenCycleRenewed::class,
            StreamIteratorSet::class => WhenBatchLoaded::class,
            CheckpointInserted::class => WhenCheckpointInserted::class,
            EventStreamDiscovered::class => WhenEventStreamDiscovered::class,
        ]);
    }
}
