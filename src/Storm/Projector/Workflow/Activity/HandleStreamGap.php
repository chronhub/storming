<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Projector\Subscription\InteractWithManagement;
use Storm\Projector\Workflow\Management\ProjectionStored;
use Storm\Projector\Workflow\Notification\AfterHandleStreamGap;
use Storm\Projector\Workflow\Notification\BeforeHandleStreamGap;
use Storm\Projector\Workflow\Process;

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
    public function __invoke(Process $process): void
    {
        $process->dispatch(BeforeHandleStreamGap::class);

        $hasGap = $process->recognition()->hasGap();

        if ($hasGap) {
            $process->recognition()->sleepOnGap();

            $isBatchStreamReset = $process->metrics()->processed === 0;

            if (! $isBatchStreamReset) {
                $process->dispatch(new ProjectionStored());
            }
        }

        $process->dispatch(AfterHandleStreamGap::class);
    }
}
