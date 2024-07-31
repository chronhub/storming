<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Projector\Workflow\Notification\AfterHandleStreamGap;
use Storm\Projector\Workflow\Notification\BeforeHandleStreamGap;
use Storm\Projector\Workflow\Process;

final class HandleQueryStreamGap
{
    public function __invoke(Process $process): void
    {
        $process->dispatch(BeforeHandleStreamGap::class);

        $hasGap = $process->recognition()->hasGap();

        if ($hasGap) {
            $process->recognition()->sleepOnGap();
        }

        $process->dispatch(new AfterHandleStreamGap());
    }
}
