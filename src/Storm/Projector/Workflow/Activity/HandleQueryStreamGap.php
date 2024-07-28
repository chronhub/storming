<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\Command\SleepOnGap;
use Storm\Projector\Workflow\Notification\Promise\HasGap;
use Storm\Projector\Workflow\Stage\AfterHandleStreamGap;
use Storm\Projector\Workflow\Stage\BeforeHandleStreamGap;

final class HandleQueryStreamGap
{
    public function __invoke(NotificationHub $hub): bool
    {
        $hub->emit(BeforeHandleStreamGap::class);

        if ($hub->await(HasGap::class)) {
            $hub->emit(SleepOnGap::class);
        }

        $hub->emit(AfterHandleStreamGap::class);

        return true;
    }
}
