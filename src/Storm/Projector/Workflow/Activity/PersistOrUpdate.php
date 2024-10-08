<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Closure;
use Storm\Projector\Projection\Events\ProjectionLockUpdated;
use Storm\Projector\Projection\Events\ProjectionStored;
use Storm\Projector\Workflow\Process;

final readonly class PersistOrUpdate
{
    /**
     * When running blank, we either update the lock after sleeping,
     * or, store the projection snapshot.
     */
    public function __invoke(Process $process, Closure $next): Closure|bool
    {
        $hasGap = $process->recognition()->hasGap();

        if (! $hasGap) {
            if ($process->metrics()->isBatchStreamBlank()) {
                $process->batch()->sleep();

                $process->dispatch(new ProjectionLockUpdated);
            } else {
                $process->dispatch(new ProjectionStored);
            }
        }

        return $next($process);
    }
}
