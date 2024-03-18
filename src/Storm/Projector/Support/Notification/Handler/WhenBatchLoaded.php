<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Notification\Handler;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Support\Notification\Batch\BatchLoaded;
use Storm\Projector\Support\Notification\Stream\StreamIteratorSet;

final class WhenBatchLoaded
{
    public function __invoke(NotificationHub $hub, StreamIteratorSet $event): void
    {
        $hub->expect(BatchLoaded::class, $event->iterator !== null);
    }
}
