<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Closure;
use Storm\Projector\Workflow\Process;

final readonly class SleepForQuery
{
    public function __invoke(Process $process, Closure $next): Closure|bool
    {
        $hasGap = $process->recognition()->hasGap();

        if (! $hasGap) {
            $process->batch()->sleep();
        }

        return $next($process);
    }
}
