<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Support\Notification\Checkpoint\CheckpointInserted;
use Storm\Projector\Support\Notification\Cycle\CycleBegan;
use Storm\Projector\Support\Notification\Cycle\CycleRenewed;
use Storm\Projector\Support\Notification\Handler\WhenBatchLoaded;
use Storm\Projector\Support\Notification\Handler\WhenCheckpointInserted;
use Storm\Projector\Support\Notification\Handler\WhenCycleBegin;
use Storm\Projector\Support\Notification\Handler\WhenCycleRenewed;
use Storm\Projector\Support\Notification\Handler\WhenEventStreamDiscovered;
use Storm\Projector\Support\Notification\Stream\EventStreamDiscovered;
use Storm\Projector\Support\Notification\Stream\StreamIteratorSet;

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
