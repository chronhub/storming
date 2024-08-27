<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Projector\Workflow\Process;

use function pcntl_signal_dispatch;

final class DispatchSignal
{
    public function __invoke(Process $process): void
    {
        if ($process->option()->getSignal()) {
            pcntl_signal_dispatch();
        }
    }
}
