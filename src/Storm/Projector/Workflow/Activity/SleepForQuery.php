<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Projector\Workflow\Process;

final readonly class SleepForQuery
{
    public function __invoke(Process $process): void
    {
        $hasGap = $process->recognition()->hasGap();

        if (! $hasGap) {
            $process->batch()->sleep();
        }
    }
}
