<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Projector\Workflow\Management\ProjectionLockUpdated;
use Storm\Projector\Workflow\Management\ProjectionStored;
use Storm\Projector\Workflow\Process;

final readonly class PersistOrUpdate
{
    /**
     * When running blank, we either update the lock after sleeping,
     * or, store the projection snapshot.
     */
    public function __invoke(Process $process): void
    {
        $hasGap = $process->recognition()->hasGap();

        if (! $hasGap) {
            if ($process->metrics()->isBatchStreamBlank()) {
                $process->batch()->sleep();

                $process->dispatch(new ProjectionLockUpdated());
            } else {
                $process->dispatch(new ProjectionStored());
            }
        }
    }
}
