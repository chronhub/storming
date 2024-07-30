<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Agent;

use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\ShouldAgentSubscribe;
use Storm\Projector\Workflow\Notification\ShouldTerminateWorkflow;
use Storm\Projector\Workflow\WorkflowContext;

class StopAgent implements ShouldAgentSubscribe
{
    public function subscribe(WorkflowContext $workflowContext, ContextReader $context): void
    {
        foreach ($context->haltOnCallback() as $callback) {
            $this->stopWhen($workflowContext, $callback);
        }
    }

    /**
     * Stop the projector when the given callback returns true.
     *
     * @param callable(WorkflowContext): bool $callback
     */
    protected function stopWhen(WorkflowContext $workflowContext, callable $callback): void
    {
        $workflowContext->listenTo(ShouldTerminateWorkflow::class, function () use ($workflowContext, $callback): void {
            // prevents stopping the projector when the projection is already terminated
            $isTerminated = $workflowContext->isSprintTerminated();

            if (! $isTerminated && $callback($workflowContext) === true) {
                $workflowContext->sprint()->halt();
            }
        });
    }
}
