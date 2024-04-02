<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Handler;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\Batch\BatchLoaded;
use Storm\Projector\Workflow\Notification\Stream\StreamIteratorSet;

final class WhenBatchLoaded
{
    public function __invoke(NotificationHub $hub, StreamIteratorSet $event): void
    {
        $hub->expect(BatchLoaded::class, $event->iterator !== null);
    }
}
