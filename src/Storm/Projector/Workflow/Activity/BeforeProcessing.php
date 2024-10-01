<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Closure;
use Storm\Projector\Workflow\Input\ConditionallyStartWorkflow;
use Storm\Projector\Workflow\Process;

final class BeforeProcessing
{
    public function __invoke(Process $process, Closure $next): Closure|bool
    {
        $process->call(new ConditionallyStartWorkflow);

        return $next($process);
    }
}
