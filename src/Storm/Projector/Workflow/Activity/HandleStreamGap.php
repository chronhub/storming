<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Subscription\InteractWithManagement;
use Storm\Projector\Workflow\Notification\Command\SleepOnGap;
use Storm\Projector\Workflow\Notification\Management\ProjectionStored;
use Storm\Projector\Workflow\Notification\Promise\HasGap;
use Storm\Projector\Workflow\Notification\Promise\IsBatchStreamReset;
use Storm\Projector\Workflow\Stage\AfterHandleStreamGap;
use Storm\Projector\Workflow\Stage\BeforeHandleStreamGap;

final class HandleStreamGap
{
    /**
     * When a gap is detected, we first, sleep for a while,
     * to let the remote storage to fix it.
     * Then we store the projection if some stream events
     * still need to be processed before the gap detection.
     *
     * @see InteractWithManagement@persistWhenThresholdIsReached
     */
    public function __invoke(NotificationHub $hub): bool
    {
        $hub->emit(BeforeHandleStreamGap::class);

        if ($hub->await(HasGap::class)) {
            $hub->emit(SleepOnGap::class);

            if (! $hub->await(IsBatchStreamReset::class)) {
                $hub->emit(new ProjectionStored());
            }
        }

        $hub->emit(AfterHandleStreamGap::class);

        return true;
    }
}
