<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\Command\BatchStreamSleep;
use Storm\Projector\Workflow\Notification\Promise\HasGap;

final readonly class SleepForQuery
{
    public function __invoke(NotificationHub $hub): bool
    {
        if (! $hub->await(HasGap::class)) {
            $hub->emit(BatchStreamSleep::class);
        }

        return true;
    }
}
