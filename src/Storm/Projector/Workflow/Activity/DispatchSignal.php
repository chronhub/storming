<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Closure;
use Storm\Projector\Workflow\Process;

use function pcntl_signal_dispatch;

final class DispatchSignal
{
    public function __invoke(Process $process, Closure $next): Closure|bool
    {
        if ($process->option()->getSignal()) {
            pcntl_signal_dispatch();
        }

        return $next($process);
    }
}
