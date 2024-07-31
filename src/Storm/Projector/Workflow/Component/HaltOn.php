<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Component;

use Storm\Contract\Projector\ComponentSubscriber;
use Storm\Contract\Projector\ContextReader;
use Storm\Projector\Workflow\Input\IsSprintTerminated;
use Storm\Projector\Workflow\Notification\ShouldTerminateWorkflow;
use Storm\Projector\Workflow\Process;

class HaltOn implements ComponentSubscriber
{
    public function subscribe(Process $process, ContextReader $context): void
    {
        foreach ($context->haltOnCallback() as $callback) {
            $this->stopWhen($process, $callback);
        }
    }

    /**
     * Stop the projector when the given callback returns true.
     *
     * @param callable(Process): bool $callback
     */
    protected function stopWhen(Process $process, callable $callback): void
    {
        $process->addListener(ShouldTerminateWorkflow::class, function (Process $process) use ($callback): void {
            // prevents stopping the projector when the projection is already terminated
            $isTerminated = $process->call(new IsSprintTerminated());

            if (! $isTerminated && $callback($process) === true) {
                $process->sprint()->halt();
            }
        });
    }
}
