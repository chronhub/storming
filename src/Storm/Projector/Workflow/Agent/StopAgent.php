<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Agent;

use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\ShouldAgentSubscribe;
use Storm\Projector\Workflow\Notification\Command\SprintStopped;
use Storm\Projector\Workflow\Notification\Promise\IsSprintTerminated;
use Storm\Projector\Workflow\Notification\ShouldTerminateWorkflow;

class StopAgent implements ShouldAgentSubscribe
{
    public function subscribe(NotificationHub $hub, ContextReader $context): void
    {
        foreach ($context->haltOnCallback() as $callback) {
            $this->stopWhen($hub, $callback);
        }
    }

    /**
     * Stop the projector when the given callback returns true.
     *
     * @param callable(NotificationHub): bool $callback
     */
    protected function stopWhen(NotificationHub $hub, callable $callback): void
    {
        $hub->addEvent(ShouldTerminateWorkflow::class, function (NotificationHub $hub) use ($callback): void {
            // prevents stopping the projector when the projection is already terminated
            $isTerminated = $hub->await(IsSprintTerminated::class);

            if (! $isTerminated && $callback($hub) === true) {
                $hub->emit(SprintStopped::class);
            }
        });
    }
}
