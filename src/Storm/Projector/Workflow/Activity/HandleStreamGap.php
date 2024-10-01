<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Closure;
use Storm\Projector\Provider\Events\ProjectionStored;
use Storm\Projector\Provider\InteractWithProvider;
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
     * @see InteractWithProvider@persistWhenThresholdIsReached
     */
    public function __invoke(Process $process, Closure $next): Closure|bool
    {
        $process->dispatch(BeforeHandleStreamGap::class);

        $hasGap = $process->recognition()->hasGap();

        if ($hasGap) {
            $process->recognition()->sleepOnGap();

            $isBatchStreamReset = $process->metrics()->isReset('processed');

            if (! $isBatchStreamReset) {
                $process->dispatch(new ProjectionStored);
            }
        }

        $process->dispatch(AfterHandleStreamGap::class);

        return $next($process);
    }
}
